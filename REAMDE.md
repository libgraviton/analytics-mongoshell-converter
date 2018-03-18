## analytics-mongoshell-converter

converts a single file containing structures that are mongo shell valid into json that can be parsed in php.

it helps us to write complex mongodb aggregation pipelines in a comfortable editor using mongoshell, but
then easily convert it into the form we need in order to use it in php using the mongodb driver - which
doesn't understand mongo shell (and sadly, mongoshell does not support extended json).

it can only parse some things, so it's no magic tool and is not intended for public usage.
