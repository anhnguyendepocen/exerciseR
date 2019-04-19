

source("student.R", echo = TRUE)

if (!"res" %in% ls()) stop("Cannot find the object \"res\"")

print(res)

checkfun <- function(x) {
    solution <- matrix(1:4, ncol = 2, nrow = 2)
    if (!identical(x, solution)) stop("Result not as expected")
    cat("\n\nWell done, solution is correct ...\n\n")
}
checkfun(res)

quit("no", 0)
