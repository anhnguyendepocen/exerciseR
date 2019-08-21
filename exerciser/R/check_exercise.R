

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
            # Copy to current working directory (opencpu, temporary dir)
            file.copy(src, dst)
        }
    }
    check_and_copy_files(doc, path$exercise, getwd())

    # What we do now: combine the user script with our test script
    # and source this file.
    tmp <- c(sprintf("%s/main.R", path$user),
             sprintf("%s/exercise_tests.R", path$exercise))
    tempfile <- tempfile(fileext = ".R")
    writeLines(unlist(sapply(tmp, readLines)), tempfile)


    # Knitr::spin to convert R->md
    Rmd_file <- knitr::spin(tempfile, format = "Rmd", report = FALSE)
    # Render with rmarkdown -> html
    opts <- list(self_contained = TRUE, mathjax = TRUE)
    html_file <- rmarkdown::render(Rmd_file,
                                   output_options = opts)
    # Copy html into users home directory.
    print(html_file)
    file.copy(html_file, sprintf("%s/_ocpu_output.html", path$user))
    #cat(readLines(html_file), sep = "\n")

    ###cat("\n\nCWD of opencpu call: ", getwd(), "\n\n")
    ###x <- list.files(dir, "*")
    ###writeLines(x)

    ###xdir <- "/home/retos"
    ###cat("\n\nTrying to read something", xdir, "\n")
    ###x <- list.files(xdir, "*")
    ###writeLines(x)

    ###cat("\n\nTrying to read a file from Downloads\n")
    ###x <- readLines("/home/retos/Downloads/read.R")
    ###writeLines(x)
    ###cat("\n\n\n")

    ###print(Sys.info())

    ###return("success in R?")

    ###stopifnot(file.exists(solution))
    ###stopifnot(file.exists(test))

    #### Else sourcing the solution
    ###source(solution)

    #### Calling the test script
    ###source(test)
}


