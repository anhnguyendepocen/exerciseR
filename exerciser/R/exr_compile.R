


#' Compiles exerciseR exercises
#'
#' Compile/parse an \code{.Rmd} file which contains an exerciseR
#' exercise. 
#'
#' @param file \code{character}, name of the Rd file to be compiled.
#' @param overwrite \code{logical}, default \code{FALSE}. Prevents the user
#'      from rendering the same Rmd file again if the \code{xml} file already
#'      exists! You may not change the description/solution/tests if the exercise
#'      has already been rolled out to the users!
#' @param verbose \code{logical}.
#'
#' @details Prepares an exerciseR exercise for the UI/UX.
#' The first input argument is the Rmd (R markdown) file which contains
#' the exercise. An exercise at least consists of a 'Settings' section,
#' the 'Description' and 'Solution' plus 'Tests'.
#'
#' The Rmd file will be rendered using \code{rmarkdown::render} (Rmd to html)
#' and allows to include the standard R markdown elements. The html file will
#' be parsed to extract the required information, which will then be stored
#' in separate \code{xml} and \code{html} files as used by the exerciseR UI.
#'
#' \itemize{
#'      \item Description: the description of the exercise as shown to the users.
#'      \item Solution: solution, will be provided depending on the settings in the UX.
#'      \item Settings: contains e.g., command blacklist/whitelist, points, files included
#'            in the exercise, title, and short description. May be extended in the future.
#'      \item Tests: a set of tests, can be visible or invisible for the user. Used to
#'            test user submissions.
#' }
#'
#' @import xml2
#' @importFrom rmarkdown render
#' @author Reto Stauffer
#' @export
exr_compile <- function(file, overwrite = FALSE, verbose = FALSE) {

    # Check if 'file' is an Rmd file and exists
    stopifnot(inherits(file, "character"))
    stopifnot(grepl("\\.rmd$", tolower(file)))
    stopifnot(file.exists(file))
    stopifnot(is.logical(verbose))
    stopifnot(is.logical(overwrite))

    # Define output file names
    tmp     <- gsub("\\.Rmd", "", basename(file), ignore.case = TRUE)
    outfile <- list(xml         = file.path(dirname(file), sprintf("%s.xml", tmp)),
                    description = file.path(dirname(file), sprintf("%s_description.html", tmp)),
                    solution    = file.path(dirname(file), sprintf("%s_solution.html", tmp)),
                    tests       = file.path(dirname(file), sprintf("%s_tests.html", tmp)),
                    Rtests      = file.path(dirname(file), sprintf("%s_tests.R", tmp)))
    if (file.exists(outfile$xml) & !overwrite) {
        stop(paste(sprintf("File \"%s\" exist!", outfile$xml),
                   "Overwrite is set to FALSE to prevent rendering the exercise again!"))
    }

    # Step 1: render the Rmd file. Store result (html file)
    # in a temporary file which will be parsed in the next steps.
    if (verbose) cat(sprintf("Rendering \"%s\" via rmarkdown\n", file))
    tempfile = tempfile()
    render(file, output_file = tempfile, output_format = "html_document", quiet = TRUE,
           output_options = list(self_contained = FALSE))

    # Read rendered html file
    if (verbose) cat(sprintf("- Read html file \"%s\"\n", tempfile))
    doc <- read_html(tempfile)

    # Get the actual ElementID of the required sections.
    # An error will be raised if we cannot find any or find multiple entries.
    id <- list()
    id["settings"]    <- exr_get_level1_id(doc, "settings",    TRUE, verbose)
    id["tests"]       <- exr_get_level1_id(doc, "tests",       TRUE, verbose)
    id["solution"]    <- exr_get_level1_id(doc, "solution",    TRUE, verbose)
    id["description"] <- exr_get_level1_id(doc, "description", TRUE, verbose)

    # Extract elements by ID
    description <- exr_get_content_by_id(doc, id$description)
    solution    <- exr_get_content_by_id(doc, id$solution)
    settings    <- exr_get_content_by_id(doc, id$settings)

    # Parse settings
    settings <- exr_parse_settings(doc, id$settings, verbose = verbose)
    tests    <- exr_parse_tests(doc, id$tests, verbose = verbose)

    # --------------------------------------------
    # Create XML file for the UI
    # --------------------------------------------
    # Append blacklist/whitelist to new xml file
    exr_xml_add_settings <- function(x, key, name, values) {
        xml_add_child(xml_find_first(x, "//settings"), key)
        node <- xml_find_first(x, paste("//settings", key, sep = "/"))
        for (rec in values) {
            tmp <- read_xml(sprintf("<%1$s>%2$s</%1$s>", name, rec))
            xml_add_child(node, tmp)
        }
        invisible(x)
    }
    exr_xml_add_setting <- function(x, name, value, ...) {
        stopifnot(length(value) == 1)
        args <- as.list(match.call(expand.dots = TRUE))[-1L]
        args <- args[!names(args) %in% c("x", "name", "value")]
        # Create new xml node
        new <- read_xml(sprintf("<%1$s>%2$s</%1$s>", name, as.character(value)))
        # Append additional attributes if any
        for (n in names(args)) xml_attr(new, n) <- eval(args[[n]])
        args$`.x` <- xml_find_first(x, "//settings")
        args$`.value` <- new
        invisible(xml_add_child(xml_find_first(x, "//settings"), new))
    }

    # Write xml file used by the UI/UX
    xml <- xml_new_root("exercise")
    xml_add_child(xml, "settings")
    exr_xml_add_setting(xml, "title", settings$title)
    exr_xml_add_setting(xml, "short", settings$short)
    exr_xml_add_setting(xml, "created",
                        strftime(Sys.time(), "%Y-%m-%dT%M:%M:%SZ"),
                        timestamp = as.integer(Sys.time()))
    exr_xml_add_settings(xml, "blacklist", "cmd", settings$blacklist)
    exr_xml_add_settings(xml, "whitelist", "cmd", settings$whitelist)
    exr_xml_add_settings(xml, "files", "file", settings$files)
    exr_xml_add_setting(xml, "points", settings$points)
    write_xml(xml, outfile$xml, options = "format")
    # TODO: validate xml

    # Create new xml root (tdoc: tests document)
    tdoc <- xml_new_root("root")
    xml_add_child(tdoc, read_xml("<div id=\"tests\" class=\"section level1\"></div>"))
    tdiv <- xml_find_first(tdoc, "div[@id='tests']")
    xml_add_child(tdiv, read_xml("<h1>Tests</h1>"))
    for (n in names(tests)) {
        if (grepl("^visible", n)) {
            xml_add_child(tdiv, read_xml(sprintf("<code class=\"test-visible\">%s</code>", tests[[n]])))
            ##xml_add_child(tdiv, read_xml(sprintf("<pre class=\"test-visible\"><code>%s</code></pre>", tests[[n]])))
        } else {
            xml_add_child(tdiv, read_xml("<code class=\"test-invisible\"># hidden test</code>"));
            ##xml_add_child(tdiv, read_xml("<pre class=\"test-invisible\"><code># hidden test</code></pre>"));
        }
    }
    writeLines(as.character(tdiv), outfile$tests)

    # Write a "exercise_tests.R" file used to check the user submission.
    Rtests <- c("## Loading tinytest", "library(\"tinytest\")", "")
    for (n in names(tests)) {
        Rtests <- append(Rtests, sprintf("## Test %s", n))
        Rtests <- append(Rtests, sprintf("%s", tests[[n]]))
    }
    writeLines(Rtests, outfile$Rtests)


    # Next: store description and solution (HTML, used by the UI)
    writeLines(as.character(description), outfile$description)
    writeLines(as.character(solution),    outfile$solution)

}


#' Extract ElementID from html file
#'
#' Helper function for \code{\link[exerciser]{exr_compile}}.
#'
#' @param doc \code{xml_node} object
#' @param what character, name of the IDs to be found. Used
#'      in a regular expression (see Details)
#' @param required logical
#'
#' @details Used to find specific elements in the xml document.
#' Extracts all \code{//*/div[@class='section level1']} IDs matching
#' the pattern \code{^<what>}. Thus, if \code{what = "description"}
#' we will find the entry \code{id="description"} but also further
#' elements (e.g., \code{id="description-1"}, \code{id="description-2"}).
#'
#' The following can happen:
#' \itemize{
#'     \item No matching entry, but \code{required = TRUE}: raise an error.
#'     \item No matching entry, but \code{required = FALSE}: NA.
#'     \item Multiple matching entries found: raise an error.
#'     \item Exactely one matching entry found: return character, ID of the element.
#' }
#'
#' @return Returns \code{NA} or a \code{character}, see Details.
#' @importFrom crayon red
#' @import xml2
#' @author Reto Stauffer
exr_get_level1_id <- function(doc, what, required = TRUE, verbose = FALSE) {
    stopifnot(inherits(doc, "xml_node"))
    stopifnot(inherits(required, "logical"))
    stopifnot(inherits(verbose, "logical"))

    # Find all "section level1" entries (from rmarkdown)
    x <- xml_find_all(doc, "//*/div[@class='section level1']")
    x <- sapply(x, function(x) xml_attr(x, "id"))
    # Find IDs matching the pattern "^<what>"
    x <- x[grep(sprintf("^%s", what), x)]

    # No element found but required: fails
    if (length(x) == 0L & required) {
        stop(red(sprintf("Cannot find required entry \"%s\". Stop.", what)))
    # No element but not required: NA
    } else if (length(x) == 0L & !required) {
        return(NA)
    # Multiple elements: fail
    } else if (length(x) > 1L) {
        stop(sprintf("Multiple entries \"%s\" found!", what))
    }
    if (verbose) cat(sprintf("- Id for \"%s\" found\n", what))
    # Else return ID
    return(x)
}


#' Get the content of an xml node with a specific id
#'
#' Helper function for \code{\link[exerciser]{exr_compile}}.
#'
#' @param x \code{xml_node} object
#' @param id \code{character}, ID of the element
#' @param elem \code{character}, default \code{"div"}
#' @return Returns the content of this specific element given
#' the element (default \code{"div"}) with the specific ElementID
#' as specified on input \code{id}.
#' @importFrom xml2 xml_find_first
#'
#' @return Returns the xml/html node given input \code{elem} (default \code{"div"})
#' and \code{id}. If input \code{id = NA}, \code{NA} will be returned.
#' @author Reto Stauffer
exr_get_content_by_id <- function(x, id, elem = "div") {
    stopifnot(inherits(x, "xml_node"))
    stopifnot(inherits(id, c("character", "NA")))
    stopifnot(inherits(elem, "character"))
    # If id is NA (element does not exist, used for non-required entries),
    # simply return NA.
    if (is.na(id)) return(NA)
    # Else return the corresponding node.
    return(xml_find_first(x, sprintf("//*/%s[@id='%s']", elem, id)))
}


#' Parse settings section of a rendered exerciseR exercise
#'
#' Helper function for \code{\link[exerciser]{exr_compile}}.
#' Extracts the information from the settings section of the Rmd/html file.
#'
#' @param doc \code{xml_node} object
#' @param id \code{character}, ElementID
#' @param verbose \code{logical}
#' @param elem \code{character}, default \code{"div"}
#'
#' @details We are expecting that the settings section of an exerciseR
#' exercise contains the following items:
#' \itemize{
#'    \item blacklist: comma separated list of commands which are not allowed to
#'          be used by the user (in addition to the default ones).
#'    \item whitelist: comma separated list of commands which can be used, allows
#'          to overrule the default blacklist (and can be used to give the user
#'          a hint what should be used). However, not mandatory to be used.
#'    \item files: list of files which come along with the exercise. If present,
#'          these files will be automatically added when executing user code and
#'          the UI will prohibit the users to upload a file with one of these names
#'          not to get in conflict.
#'    \item points: numeric value, points for this exercise.
#' }
#' Note that all entries are optional!
#'
#' @return Returns a list with character vectors for elements
#' \code{blacklist}, \code{whitelist}, and \code{files}, and a numeric
#' value (or numeric \code{NA}) for the \code{points}.
#'
#' @importFrom crayon red green blue
#' @importFrom xml2 xml_find_all xml_text
#' @author Reto Stauffer
exr_parse_settings <- function(doc, id, verbose = FALSE, elem = "div") {
    stopifnot(inherits(id, "character"))
    stopifnot(inherits(doc, "xml_document"))

    # Extract content of all list elements
    x <- sapply(xml_find_all(doc, sprintf("//%s[@id='%s']/ul/li", elem, id)), function(x) xml_text(x))
    # Helper function to extract key/value information
    get_values <- function(x, key, nreq = NULL, split = TRUE) {
        x <- x[grep(sprintf("^%s:", key), x)]
        if (!is.null(nreq) && !length(x) == nreq)
            stop(sprintf("Requires %d entries for \"%s\" in section. Not found.",
                         nreq, key))
        if (length(x) == 0) return(vector("character", 0))
        # Avoid having "<" or ">" in the entries.
        if (any(grepl("(<|>)", x)))
            stop(sprintf("Found illegal characters (<, >) in \"%s\".", key))
        # Split if needed
        if (split) {
            res <- sapply(strsplit(gsub(sprintf("^%s:", key), "", x), ",")[[1]], trimws)
        } else {
            res <- trimws(sub(sprintf("^%s:", key), "", x))
        }
        return(res)
    } 
    res <- list(blacklist = get_values(x, "blacklist"),
                whitelist = get_values(x, "whitelist"),
                files     = get_values(x, "files"),
                title     = get_values(x, "title", nreq = 1L, split = FALSE),
                short     = get_values(x, "short", nreq = 1L, split = FALSE),
                points    = get_values(x, "points"))

    # Convert points to numeric
    res$points <- ifelse(length(res$points) == 0L, NA, try(as.numeric(res$points)))
    if (inherits(res$points, "try-error")) stop("Points in settings section must be numeric")
    if (verbose) {
        cat("- Settings:\n")
        cat(red(  sprintf("  Blacklist:   %s\n", paste(res$blacklist, collapse = ", "))))
        cat(green(sprintf("  Whitelist:   %s\n", paste(res$whitelist, collapse = ", "))))
        cat(blue( sprintf("  Files:       %s\n", paste(res$files, collapse = ", "))))
    }
    
    # Return de-parsed information (list)
    return(res)
}


#' Parse test section of a rendered exerciseR exercise
#'
#' Helper function for \code{\link[exerciser]{exr_compile}}.
#' Extracts the information from the tests section.
#'
#' @param doc \code{xml_node} object
#' @param id \code{character}, ElementID
#' @param verbose \code{logical}
#' @param elem \code{character}, default \code{"div"}
#'
#' @details The test section can look as follows in the markdown file:
#'  ----------------------------------
#'  # Tests
#'  * expect_true(...)
#'  * expect_identical(...)
#'  ## Visible
#'  * expect_true(...)
#'  ```
#'  <some custom test code>
#'  ```
#'  ## Invisible
#'  * expect_true(...)
#'  ```
#'  <some custom test code>
#'  ```
#'  ----------------------------------
#'  
#'  Tests not defined in a subsection (## Visible or ## Invisible)
#'  will be treated as "invisible" by default!
#'  Those in ## Visible will be visible to the users. Those in
#'  ## Invisible will not be shown on the UI.
#'  Tests will be executed in the sequence as shown above. Multiple
#'  Subsections are allowed (e.g., ## Visible followed by ## Invisible
#'  followed by another ## Visible subsection).
#'
#' @return Returns a list with all tests in the same order as defined
#' in the Rmd file. The name of the list elements define whether or not
#' a test is visible or not.
#'
#' @importFrom crayon red
#' @author Reto Stauffer
exr_parse_tests <- function(doc, id, verbose = TRUE, elem = "div") {
    stopifnot(inherits(id, "character"))
    stopifnot(inherits(doc, "xml_document"))

    # Helper function to extract the tests
    get_tests <- function(doc, xpath, visible = c("invisible", "visible")) {
        # Input check
        stopifnot(inherits(id, "character"))
        visible <- match.arg(visible)
        # Tests can be one-liners (list entries) or
        # code chunks. Try to find both:
        li   <- lapply(xml_find_all(doc, paste(xpath, "ul/li", sep = "/")),   function(x) xml_text(x))
        code <- lapply(xml_find_all(doc, paste(xpath, "pre/code", sep = "/")), function(x) xml_text(x))
        tests <- c(li, code)
        names(tests) <- rep(visible, length(tests))
        return(tests)
    }

    # Main level
    tests <- list()
    tests <- c(tests, get_tests(doc, sprintf("//%s[@id='%s']", elem, id), "invisible"))

    # Find subsections
    ids <- sapply(xml_find_all(doc, "//div[@id='tests']/div[@class='section level2']"),
                  function(x) xml_attr(x, "id"))
    ids <- ids[grep("^(visible|invisible).*?$", ids)]
    # For each of the subsections: parse tests as well
    for(id in ids) {
        tests <- c(tests, get_tests(doc, sprintf("//div[@id='%s']", id),
                                    ifelse(grepl("^visible", id), "visible", "invisible")))
    }

    # Rename the entries
    idx <- grep("^visible$", names(tests))
    if (length(idx) > 0L) names(tests)[idx] <- sprintf("%s_%d", names(tests[idx]), seq_along(idx))
    idx <- grep("^invisible$", names(tests))
    if (length(idx) > 0L) names(tests)[idx] <- sprintf("%s_%d", names(tests[idx]), seq_along(idx))

    if (verbose) cat(sprintf("- Found %d tests (%d visible, %d invisible)\n",
                             length(tests),
                             sum(grepl("^visible", names(tests))),
                             sum(grepl("^invisible", names(tests)))))
    return(tests)
}









