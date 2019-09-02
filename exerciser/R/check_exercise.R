

#' Check/Test solution for a specific exercise
#'
#' TODO
#'
#' @param dir character, folder containing exercises and user uploads
#' @param exercise_id integer, ID of the exercise (as in database)
#' @param user_id integer, ID of the user
#' @param exercise_hash string, combination of random hash, assignment-timestamp, and user ID
#'        (see database). Used to identify a specific exercise for a specific user
#'
#' @return No explicit return, creates output on stdout.
#'
#' @import xml2
#' @import crayon
#' @export
check_exercise <- function(dir, exercise_id, user_id, exercise_hash) {

    # Convert input "exercise_id"
    exercise_id <- try(as.integer(exercise_id))
    if (inherits(exercise_id, "try-error")) stop("Input \"exercise_id\" not convertable to integer.")
    user_id <- try(as.integer(user_id))
    if (inherits(user_id, "try-error")) stop("Input \"user_id\" not convertable to integer.")

    # Now double-check inputs
    stopifnot(inherits(exercise_id,   "integer"))
    stopifnot(inherits(user_id,       "integer"))
    stopifnot(inherits(exercise_hash, "character"))

    # Create full absolute paths which point to the exercise and the
    # user upload folder with the solution. Required to read/copy files.
    path <- list(exercise = sprintf("%s/exercises/%d", dir, exercise_id),
                 user     = sprintf("%s/uploads/user-%d/%s", dir, user_id, exercise_hash))
    stopifnot(all(sapply(path, dir.exists)))

    # Delete output file if existing
    if (file.exists(sprintf("%s/_ocpu_output.html", path$user)))
        file.remove(sprintf("%s/_ocpu_output.html", path$user))

    cat("[exR] Exercise ID:        ", exercise_id, "\n");
    cat("[exR] Exercise hash:      ", exercise_hash, "\n");
    cat("[exR] user directory:     ", dir, "\n");
    cat("[exR] working directory:  ", getwd(), "\n");

    # Read the exercise xml file
    doc <- read_xml(sprintf("%s/exercise.xml", path$exercise))

    # exercises can include files ("included files") which the user
    # does not have to upload. Check if we have such files. If so,
    # check if available and copy from the exercise directory to the
    # current working directory (opencpu: temporary folder).
    check_and_copy_files <- function(doc, from, to) {
        files <- xml_find_all(doc, "//settings/files/file")
        for (file in files) {
            file <- xml_text(file)
            src <- sprintf("%s/%s", from, file)
            dst <- sprintf("%s/%s", to, file)
            stopifnot(file.exists(src))
            cat(red(sprintf("Copy file %s -> %s\n", src, dst)))
            # Copy to current working directory (opencpu, temporary dir)
            file.copy(src, dst)
        }
    }
    check_and_copy_files(doc, path$exercise, getwd())

    # Development purposes, added to log file
    cat("----------------- begin main.R -----------------\n")
    cat(writeLines(readLines(sprintf("%s/main.R", path$user)), sep = "\n"))
    cat("------------------ end main.R ------------------\n")

    # What we do now: combine the user script with our test script
    # and source this file.
    tmp <- c("#' # Your submission",
             readLines(sprintf("%s/main.R", path$user)),
             "#' # ExerciseR Tests",
             readLines(sprintf("%s/exercise_tests.R", path$exercise)))
    tempfile <- basename(tempfile(fileext = ".R"))
    writeLines(tmp, tempfile)

    # Knitr::spin to convert R->md
    Rmd_file <- knitr::spin(tempfile, format = "Rmd", report = FALSE)

    # Render with rmarkdown -> html
    opts <- list(self_contained = TRUE, mathjax = TRUE)
    html_file <- rmarkdown::render(Rmd_file,
                                   output_options = opts)
    # Copy html into users home directory.
    doc <- read_html(html_file)
    # Find body entry
    content <- xml_find_first(doc, "//*/div[contains(@class, 'main-container')]")
    xml_attr(content, "id") <- "ocpuoutput-response"

    # Adding classes for PASSED/FAILED
    add_class_count_tests <- function(content, id) {
        # Used as return
        tests <- list(failed = 0, passed = 0, total = NA)

        # Find 'pre' entries
        xpath <- sprintf("//*/div[@id='%s']/pre", id)
        for (pre in xml_find_all(content, xpath)) {
            code <- xml_find_first(pre, "code")
            if (grepl("----\\sFAILED", xml_text(code))) {
                tests$failed <- tests$failed + 1
                if (is.na(xml_attr(pre, "class"))) {
                    xml_attr(pre, "class") <- "failed"
                } else {
                    xml_attr(pre, "class") <- paste(xml_attr(pre, "class"), "failed")
                }
            } else if (grepl("##\\sError", xml_text(code), perl = TRUE)) {
                tests$failed <- tests$failed + 1
                if (is.na(xml_attr(pre, "class"))) {
                    xml_attr(pre, "class") <- "error"
                } else {
                    xml_attr(pre, "class") <- paste(xml_attr(pre, "class"), "error")
                }
            } else if (grepl("----\\sPASSED", xml_text(code))) {
                tests$passed <- tests$passed + 1
                if (is.na(xml_attr(pre, "class"))) {
                    xml_attr(pre, "class") <- "passed"
                } else {
                    xml_attr(pre, "class") <- paste(xml_attr(pre, "class"), "passed")
                }
            }
        }

        # Sump up failed and passed tests (total count)
        tests$total <- tests$failed + tests$passed
        invisible(tests)
    }

    # User submission: just add css classes (color), ignore counts (return)
    add_class_count_tests(content, "your-submission")

    # Exerciser tests: add css classes (color), store return.
    tests <- add_class_count_tests(content, "exerciser-tests")

    # Adding a special HTML element which contains the number of
    # tests failed. Used by the UI/UX to decide whether or not the user
    # successfully solved the exercise (or not).
    xml_add_child(content,
                  read_xml(paste("<pre id=\"ocpu-tests-failed\">",
                                 "# ExerciseR Test Summary:\nTests faild:",
                                 sprintf("<span class=\"absolute\">%d/%d</span>",
                                         tests$failed, tests$total),
                                 sprintf("<span class=\"success\">(Success rate: %.1f percent)</span>",
                                         tests$passed / tests$total * 100),
                                 "</pre>")),
                  .where = 0L)

    ocpu_output <- sprintf("%s/_ocpu_output.html", path$user)
    cat(green(sprintf("[exR] Write output into \"%s\"\n", ocpu_output)))
    writeLines(as.character(content), ocpu_output, sep = "\n")

    # Write a small xml file which contains the meta information
    # about the execution of the user script including
    # - number of tests run
    # - number of tests failed
    # - ...
    meta <- read_xml("<tests></tests>")
    xml_add_child(xml_find_first(meta, "/tests"),
                  read_xml(sprintf("<total>%d</total>", tests$total)))
    xml_add_child(xml_find_first(meta, "/tests"),
                  read_xml(sprintf("<passed>%d</passed>", tests$total - tests$failed)))
    xml_add_child(xml_find_first(meta, "/tests"),
                  read_xml(sprintf("<failed>%d</failed>", tests$failed)))

    ocpu_meta   <- sprintf("%s/_ocpu_output.xml", path$user)
    cat(green(sprintf("[exR] Write output into \"%s\"\n", ocpu_meta)))
    write_xml(meta, ocpu_meta)

    # No return!
    invisible(NA)
}


