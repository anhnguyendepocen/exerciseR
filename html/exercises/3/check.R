

# Else source the script
source("/tocheck/main.R", echo = TRUE, max.deparse.length = Inf)

# Check if the object exists
if (!"myfun" %in% objects()) stop("\n# Cannot find object \"myfun\".")
if (!is.function(myfun)) stop("\n# Object myfun is not a function.")

checkfun <- function(fun) {
    x <- sample(c("Reto", "Ben", "Lea", "Heidi"))

    if (!identical(fun(x), which(x == "Reto"))) {
        cat("\n# Nope, that is not correct.")
        quit("no", status = 1001);
    }
    cat("\n\n# Well done, all fine!")
}
checkfun(myfun)

