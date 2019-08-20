


#' Check/Test solution for a specific exercise
#'
#' TODO
#'
#' @param dir character, folder containing exercises and user uploads.
#' @param exercise_id integer, ID of the exercise (as in database).
#' @param exercise_hash string, combination of random hash, assignment-timestamp, and user ID
#'        (see database). Used to identify a specific exercise for a specific user.
#'
#' @return No explicit return, creates output on stdout.
#'
#' @export
check_exercise <- function(dir, exercise_id, exercise_hash) {

    # Convert input "exercise_id"
    exercise_id <- try(as.integer(exercise_id))
    if (inherits(exercise_id, "try-error")) stop("Input \"exercise_id\" not convertable to integer.")
    stopifnot(inherits(exercise_id,   "integer"))
    stopifnot(inherits(exercise_hash, "character"))


    cat("[exR] Exercise ID:     ", exercise_id, "\n");
    cat("[exR] Exercise hash:   ", exercise_hash, "\n");
    cat("[exR] user directory:  ", dir, "\n");

    return("foobar")

    cat("\n\nCWD of opencpu call: ", getwd(), "\n\n")
    x <- list.files(dir, "*")
    writeLines(x)

    xdir <- "/home/retos"
    cat("\n\nTrying to read something", xdir, "\n")
    x <- list.files(xdir, "*")
    writeLines(x)

    cat("\n\nTrying to read a file from Downloads\n")
    x <- readLines("/home/retos/Downloads/read.R")
    writeLines(x)
    cat("\n\n\n")

    print(Sys.info())

    return("success in R?")

    stopifnot(file.exists(solution))
    stopifnot(file.exists(test))

    # Else sourcing the solution
    source(solution)

    # Calling the test script
    source(test)
}


