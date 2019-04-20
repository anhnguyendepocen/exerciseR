


# First: check if the user uses the mean or length 
# function. If so, stop.
mean <- function(x) {
    cat("# You are not allowed to use the function mean(...)\n")
    quit("no", status = 999)
}
length <- function(x) {
    cat("# You are not allowed to use the function length(...)\n")
    quit("no", status = 999)
}
sum <- function(x) {
    cat("# You are not allowed to use the function sum(...)\n")
    quit("no", status = 999)
}

# Check if the user uses mean( or length(
content <- readLines("/tocheck/main.R")
if (any(grepl("(\\s|-|=)mean\\s?\\(", content, perl = TRUE))) {
    cat("# You are not allowed to use the function mean(...)\n")
    quit("no", status = 999)
}
if (any(grepl("length\\s?\\(", content, perl = TRUE))) {
    cat("# You are not allowed to use the function length(...)\n")
    quit("no", status = 999)
}
if (any(grepl("sum\\s?\\(", content, perl = TRUE))) {
    cat("# You are not allowed to use the function sum(...)\n")
    quit("no", status = 999)
}

# Else source the script
source("/tocheck/main.R", echo = TRUE, max.deparse.length = Inf)

# Check if the object exists
if (!"mymean" %in% objects()) stop("\n# Cannot find object \"mymean\".")
if (!is.function(mymean)) stop("\n# Object mymean is not a function.")

checkfun <- function(fun) {
    set.seed(10)
    x <- rnorm(100)
    if (!isTRUE(all.equal(base::mean(x), fun(x)))) {
        cat("\n# Result is not correct");
        quit("no", status = 1001);
    }
    cat("\n\n# Well done, all fine!")
}
checkfun(mymean)

