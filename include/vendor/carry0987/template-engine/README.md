# TemplateEngine
[![Packagist](https://img.shields.io/packagist/v/carry0987/template-engine.svg?style=flat-square)](https://packagist.org/packages/carry0987/template-engine)  
A lightweight and fast PHP template engine, using Composer, featuring caching abilities, customizable cache lifetime, template inheritance, and support for Redis and MySQL.

This powerful yet simple template engine provides the flexibility to store and cache your templates in various ways. Whether you're looking to save your templates locally, cache them with longevity in mind, nest template files for complex designs, utilize persistent storage with Redis, or manage templates through MySQL databases, this engine is equipped to handle your needs efficiently and with ease.

## Installation
```bash
composer require carry0987/template-engine
```

## Features
- Support pure html as template
- Support CSS, JS file cache
- Support CSS model cache
- Auto minify CSS cache
- Cache lifetime

## Usage
You can choose saving version of template file to Database or Redis  

Save to the database
```php
// Database configuration
$config = array(
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'template',
    'username' => 'root',
    'password' => ''
);
$database = new DBController($config);
```

Save to Redis
```php
// Redis configuration
$redisConfig = array(
    'host' => 'redis',
    'port' => 6379,
    'password' => '',
    'database' => 1
);
$redis = new RedisController($redisConfig);
```

## Cache CSS &amp; JS File
#### CSS Cache
**Cache specific part of CSS**  
html
```html
<link href="{loadcss common.css index}" rel="stylesheet" type="text/css">
```
You can use variable as `specific part`
```html
<!--{eval $current_page = 'index'}-->
<link href="{loadcss model.css $current_page}" rel="stylesheet" type="text/css">
```

CSS
```css
/*[index]*/
.header {
    display: block;
}

.link {
    color: blue;
}
/*[/index]*/
```
Output:
HTML
```html
<link href="cache/model_index.css?v=Ad0Dwf8" rel="stylesheet" type="text/css">
```
`cache/model_index.css`
```css
/* index */
.header{display:block}.link{color:blue}
/* END index */
```

Also, with **`array`**
```html
<!--{eval $current_page = array('index','test')}-->
<link href="{loadcss model.css $current_page}" rel="stylesheet" type="text/css">
```
CSS
```css
/*[index]*/
.header {
    display: block;
}

.link {
    color: blue;
}
/*[/index]*/

/*[test]*/
.header {
    display: inline-block;
}

.link {
    color: red;
}
/*[/test]*/
```
Output:
HTML
```html
<link href="cache/model_MULTIPLE.css?v=Ad0Dwf8" rel="stylesheet" type="text/css">
```
`cache/model_MULTIPLE.css`
```css
/* index */
.header{display:block}.link{color:blue}
/* END index */
/* test */
.header{display:inline-block}.link{color:red}
/* END test */
```

**Directly cache CSS file**  
html
```html
<link href="{loadcss common.css}" rel="stylesheet" type="text/css">
```
Output:
```html
<link href="static/css/common.css?v=Ad0Dwf8" rel="stylesheet" type="text/css">
```

#### JS Cache
html
```html
<script src="{loadjs jquery.min.js}" type="text/javascript"></script>
```
Output:
```html
<script src="static/js/jquery.min.js?v=B22PE8W" type="text/javascript"></script>
```

#### Static File
html
```html
<img src="{static img/logo.png}" alt="logo">
```
Output:
```html
<img src="static/img/logo.png" alt="logo">
```

## Functions
#### **`echo`** function
html
```html
<span>{$value}</span>
```
PHP
```php
<span><?php echo $value; ?></span>
```

#### **`assign variable`** function
>Note: don't put any php script into **`block`** tag

html
```html
<!--{block test}-->
<span>html content</span>
<!--{/block}-->
```
PHP
```php
<?php
$test = <<<EOF

<span>html content</span>

EOF;
?>
```

#### **`if`** function
html
```html
<!--{if expr1}-->
    statement1
<!--{elseif expr2}-->
    statement2
<!--{else}-->
    statement3
<!--{/if}-->
```
PHP
```php
<?php if(expr1) { ?>
    statement1
<?php } elseif(expr2) { ?>
    statement2
<?php } else { ?>
    statement3
<?php } ?>
```

#### **`loop`** function (without key)
html
```html
<!--{loop $array $value}-->
    <span>username</span>
<!--{/loop}-->
```
PHP
```php
<?php foreach($array as $value) {?>
    <span>username</span>
<?php } ?>
```

#### **`loop`** function (with key)
html
```html
<!--{loop $array $key $value}-->
    <span>{$key} = {$value}</span>
<!--{/loop}-->
```
PHP
```php
<?php foreach($array as $key => $value) {?>
    <span><?php echo $key; ?> = <?php echo $value; ?></span>
<?php } ?>
```

#### **`eval`** function
html
```html
<!--{eval $value = 1+2}-->
<span>{$value}</span>
```
PHP
```php
<?php eval $value = 1+2;?>
<span><?php echo $value; ?></span>
```

## **`PRESERVE`** mark
html
```html
<!--{PRESERVE}-->
<span>html content</span>
<!--{/PRESERVE}-->
/*{PRESERVE}*/
<script>
const value = 1+2;
document.querySelector('span').innerHTML = `Value: ${value}`;
</script>
/*{/PRESERVE}*/
```
PHP
```php
<span>html content</span>
<script>
const value = 1+2;
document.querySelector('span').innerHTML = `Value: ${value}`;
</script>
```
