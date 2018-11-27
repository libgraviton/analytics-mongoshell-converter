## analytics-mongoshell-converter

**this repository is not fit for public usage!**

it helps us to write complex mongodb aggregation pipelines in a comfortable editor using mongoshell, but
then easily convert it into the form we need in order to use it in php using the mongodb driver - which
doesn't understand mongo shell (and sadly, mongoshell does not support extended json).

it parses *.js file using a Lexer and a Parser to generate valid PHP code - one class per pipeline.
