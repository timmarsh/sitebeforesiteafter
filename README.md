sitebeforesiteafter
===================

a screen shot tool, runs before and after grabs on a list of urls, and reports the differences.
basically a little script that takes screenshots of webpages, using [phantomjs](http://phantomjs.org/). and then takes some more. and compares the difference

why ?, well, on a mass deployment - its good to get an idea of what changed visually, or how frequently customers change their content. or whether that fancy upgrade that couldn't affect anything, has affected something visual.

it basically does a few checks, and dumps the different pixels between before and after to a new file.

[rasterize.js](https://github.com/ariya/phantomjs/blob/master/examples/rasterize.js) is basically a simple tweak to the default example from phantomjs

the exec function in here is almost verbatim (if not exactly verbatim) from [php.net](http://php.net/manual/en/function.exec.php)

It'll be idiosyncratic in places, really badly written, poor code with no standards, no tests.. but it is just a useful script I want to be able to get to , and keep updated.

## running it

```
imgsite -b //takes the before
```

..then change something on the server

```
imagesite -a //takes the after
```

now run the compare

```
imgsite -c //does the compare
```

theres some arguments that you can override things, the most useful is probably the path to your install of phantoms , which is -p

## what doesnt it do
* make the tea
* obey robots.txt (but it does sleep for 10 seconds between each capture)
* pretty much everything except take a before and after screenshot of a webpage, and then compare them

## author
me - thats @timmarsh on twitter.

## licence 
basically I dont know, its a script - for me, put here.. lets say MIT for now as that seems the coolest one. but if I find you plotting world domination with this, I'll change that to something less permissive.



