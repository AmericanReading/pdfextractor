# PDF Extractor

PDF Extractor is a CLI utility for converting a PDF to individual JPEG images.

## Docker

The Docker image `americanreadingy/pdfextractor` provides the PDF Extractor app with all dependencies. You can use this image without installing PHP or ImageMagick locally.

### Obtaining the Image

To build the Docker image, run:

```bash
docker build -t americanreading/pdfextractor .
```

To pull the image from Docker Hub, run:

```bash
docker pull americanreading/pdfextractor
```

### Using

#### Paths

The image specifies `/data` as the default working directory. For eaiest use, mount the directory containing your files as `/data` and provide the input and output paths as relative paths using the `-i` and `-o` arguments.

For example, if the current directory contains a the file "my-cat.pdf", and you would like to convert it and output the result to the directory "my-cat-converted", use this command:

```bash
docker run --rm -it --name pdfextractor \
    -v $(pwd):/data \
    americanreading/pdfextractor \
      -i my-cat.pdf \
      -o my-cat-converted
```

#### Configuration Files

To specify a config file, mount it as `/etc/pdfextractor.json`.

```bash
docker run --rm -it --name pdfextractor \
    -v $(pwd):/data \
    -v /home/your_user/.pdfextractor.json:/etc/pdfextractor.json \
    americanreading/pdfextractor \  
        -i my-cat.pdf \
        -o my-cat-converted
```

### Development

** Build a utility image **
(this does not need to be published to Docker Hub)

```
docker build -t util -f docker/php.Dockerfile .
```

** Install Composer dependencies **

```
docker run --rm -it -v $(pwd):/app -w /app/src util composer install
```

** Build the Phar **

```
docker run --rm -it -v $(pwd):/app -w /app util php build.php
```
