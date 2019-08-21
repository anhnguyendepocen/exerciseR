

# Export user login details, used for testing
test:
	make install
	Rscript -e "library('exerciser'); testthat::test_dir('tests/testthat')"

## Code coverage and report.
## Requires 'covr' to be installed. Saves the coverage summary to '_coverage.rds'.
#coverage:
#	##make install &&
#	Rscript -e "library('covr'); x <- package_coverage(); saveRDS(x, file = '_coverage.rds'); report(x)"
#	#Rscript -e "devtools::test_coverage(show_report = TRUE)"

## Build site, create documentation.
#doc:
#	make install && \
#	Rscript -e "pkgdown::build_site()"

# Package check
check:
	make install && Rscript -e "devtools::check()"

#install: SHELL:=/bin/bash
install:
	Rscript -e "devtools::load_all(); devtools::document()" && \
	Rscript -e "devtools::install(upgrade = 'always')"

