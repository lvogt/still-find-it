# still FindIt!

This is a new interface to the ICSD database. It requires the [ICSD Intranet ICSD](http://www2.fiz-karlsruhe.de/icsd_intranet.html) database available from FIZ. The project is inspired by ICSD-for-WWW by Peter Hewat.

---
## Please remember this a work in progess! It is *not recommended* for production use.
---

### Setup
* Import the (MySQLv5) database as received from FIZ. Make sure the database can be accessed. Check the [ICSD-for-WWW manual](http://icsd.ill.fr/icsd/install/index.html) if you have trouble.
* Copy all files over to your server. Rename database.dist.inc.php to database.inc.php and update the MySQL host, user, password and database name accordingly.

### Server requirements
* basically an webserver (apache, nginx, etc) should work
* PHP version 5.2 or greater (anything below needs a JSON_en/decode replacement)
* only MySQLi is currently supported
* session support is not needed but recommended (might change in the future)
* MySQL version 5+ (tested/developed with 5.5 - **no** idea what is acutally needed...)

### Frontend requirements
* tested with *recent* Firefox and Chrome
* IE will **not** work - and might never...
* no idea about Edge
* JavaScript!

### Features
* Search the database with a similar text interface as ICSD-for-WWW
* select and reorder result columns
* view full database entry
* download single entries as CIF (not "perfect" yet)
* view structure directly in browser using JSmol (needs better default values and controls)
