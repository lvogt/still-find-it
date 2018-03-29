# still FindIt!

This is a new interface to the ICSD database. It requires the [ICSD Intranet ICSD](http://www2.fiz-karlsruhe.de/icsd_intranet.html) database available from FIZ. The project is inspired by ICSD-for-WWW by Peter Hewat.

---
## Please remember this a work in progress! It is *not recommended* for production use.
---

### Setup
* Import the (MySQLv5) database as received from FIZ. Make sure the database can be accessed. Check the [ICSD-for-WWW manual](http://icsd.ill.fr/icsd/install/index.html) if you have trouble.
* Copy all files over to your server. Rename database.dist.inc.php to database.inc.php and update the MySQL host, user, password and database name accordingly.

### Server requirements
* basically any webserver (apache, nginx, etc) should work
* PHP version 5.2 or greater (anything below needs a JSON_en/decode replacement)
* only MySQLi is currently supported
* session support is not needed but recommended (might change in the future)
* MySQL version 5+ (tested/developed with 5.5 - **no** idea what is actually needed...)

### Frontend requirements
* tested with *recent* Firefox and Chrome
* IE will **not** work - and might never...
* no idea about Edge
* JavaScript!

### Features
* search the database with a similar text interface as ICSD-for-WWW
* select and reorder result columns
* view full database entry
* download single entries as CIF (not "perfect" yet)
* view structure directly in browser using JSmol (needs better default values and controls)

### Some hints for the (current) search interface
* input fields which expect text take "%" as a wildcard
* input fields which expect numbers usually also take ranges ("2.3 - 4" or "1 to 3") or limits using <, >, <=, >=
* searching for element composition:
    * simple listing of elements or groups (see below)
    * elements and groups may be followed by index which is matched against the **complete** sum formula
        * "Au1 Cl" returns Au1Cl3, Au1Cl1, etc. - also AuClO if you do not limit the element count or exclude elements!
        * "Au1 HAL3" returns all gold(III) halides and more (see above)
    * OR operation: element list in brackets
        * "Au (HAL3 or F12)" returns compounds containing gold and 3 halide atoms (same element!) or 12 fluorine atoms
    * structure fragments in quotes will be matched against the structured formula
        * "P2 O7" returns diphosphates
    * the input field to exclude elements accepts element symbols or groups
    * to search for compounds, which exclusively contain the elements listed in the composition input field, enter "ALL" into the exclude input field
* searching for lattice constants:
    * if only a number is entered, a range of +-3% for axes and +-1.5% for angles is automatically applied
    * fast search: enter either three, four or six numbers or number ranges to search for either a, b and c with any angle; a, b, c and beta with alpha and gamma = 90; or a, b, c, alpha, beta and gamma
    * lattice constants can be specified directly; you can skip constants but they need to be ordered (a, b, c, alpha, beta, gamma)
        * e. g. "a=3-5 alpha=90"
* some input fields already display a "wrench" icon which opens a little helper for a more "mouse-friendly" input method - more available "helpers" are planed...
* all input fields display an example search as placeholder text

### Element groups
* in most cases: first element + G (for groups) or + P (for periods)
* main groups: ALK, ALE, BG, TET, PNC, CHA, HAL, NGS
* transition metal groups: SCG, TIG, VG, CRG, MNG, FEG, COG, NIG, CUG, ZNG
* periods: HP, LIP, NAP, KP, RBP, CSP, FRP
* ACT = actinides; LAN = lanthanides
* TRM = all trans. metals; PTM = platinum metals; SCP, YP, LAP, ACP = 3d/4d/5d/6d metals
* MET = all metals; NOM = all non-metals
