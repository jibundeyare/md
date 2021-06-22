# Md

This project compiles a Markdown source to an HTML file.
The resulting HTML file includes Bootstrap for web design and Prism for
code highlighting.

Mainly, it uses the following packages :

- [thephpleague/commonmark: Highly-extensible PHP Markdown parser which fully supports the CommonMark and GFM specs.](https://github.com/thephpleague/commonmark)
- [Bootstrap · The most popular HTML, CSS, and JS library in the world.](https://getbootstrap.com/)
- [Prism](https://prismjs.com/)

## Install

    composer install
    npm install

## Usage

    make
    ./md.sh src/test.md
    php -S localhost:8000 -t public

And open [http://localhost:8000/test.html](http://localhost:8000/test.html).

## Syntax

    php md.php --head assets/html/head.html --tail assets/html/tail.html -d public src/test.md

