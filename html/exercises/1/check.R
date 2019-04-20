

source("/tocheck/main.R", echo = TRUE)



# Check the solution
checkfun <- function(result) {
    # Solution
    solution <- matrix(nrow = 3, ncol = 3)
    if (!identical(result, solution)) {
        cat("\n\n### [INFO] The solution is not correct")
        quit("no", status = 999)
    }
    cat("\n\n### [INFO] Well done, all fine!")
}

checkfun(x)
