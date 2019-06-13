# PHP Examples

I had to teach myself PHP at work so I've decided to compile a few examples of what we use PHP for. 

Before I came, everything in the company was written in *just* PHP (or Paradox but that's a different story), which I had heard of but had never been exposed to in school. All of our backend is still currently written in PHP, and I am, for now, continuing that. This is a compilation of PHP examples that I've done similarly at work.

## Topics
- echo | using `echo` to inject html or javascript into client side 
- mysqli | connecting to a MariaDB SQL server using a `mysqli` PHP object
  - `mysqli()->query()` `mysqli()->errno` `mysqli()->error`
  - `$mysqli_result->fetch_array()` 
- odbc | connecting to locally hosted Paradox tables using the `odbc` protocol
  - `odbc_connect()`
  - `odbc_exec()`
  - `odbc_result_all()`
  - `odbc_result()`
  - `odbc_fetch_array()` `odbc_fetch_row()`
  - `odbc_errormsg()`
- `include()` vs `include_once()` vs `require()` vs `require_once()` usage
- superglobals  | `$_POST` `$_GET` `$_SERVER` `$_FILE` and `$_SESSION`
- interfaces and classes | interface example which implements functions to display different tables
- Strings
  - `strpos()`
  - `str_replace()` `preg_replace()`
  - `substr()`
  - `strtoupper()` `strtolower()` `ucfirst()` `ucwords()`
  - string concatnation 
- Numerical values
  - `intval()`, `floatval()`
  - `number_format()`
- Arrays
  - `array_key_exists()`
  - shorthand array initialization | `$arr = []`
  - `count()` vs `sizeof()`
  - `explode()` `implode()`
- JSON
  - `json_encode()` `json_decode()`
- Dates
  - `date()` `date()->format()`
- header | setting headers for switching locations or changing application types 
- `global` keyword used in functions


