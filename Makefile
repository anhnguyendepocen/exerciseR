
build:
	sudo docker build -t=r-docker .

npm:
	(cd html && \
		npm install node.js && \
		npm install jQuery && \
		npm install boostrap && \
		npm install codemirror)

run:
	sudo docker run r-docker --cpus 1 --cpu-rt-period 10000 --memory 2g

test:
	sudo docker run -ti --rm -v "${PWD}":/scripts -w /scripts -u docker r-base Rscript test-script.R
test2:
	sudo docker run -ti --rm -v "${PWD}":/scripts -w /scripts -u docker r-base Rscript test-fails.R
check:
	sudo docker run -ti --rm -v "${PWD}":/scripts -w /scripts -u docker r-base Rscript check.R

dev:
	php -S localhost:5000 -t html
