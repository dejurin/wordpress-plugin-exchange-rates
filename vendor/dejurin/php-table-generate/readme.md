# PHP Table Generate
Packagist: https://packagist.org/packages/dejurin/php-table-generate

Simple PHP HTML table generator by CodeIgniter.

## Installation

Install this package via [Composer](https://getcomposer.org/).

```
composer require dejurin/php-table-generate
```

Or edit your project's `composer.json` to require `dejurin/php-table-generate` and then run `composer update`.

```json
"require": {
    "dejurin/php-table-generate": "^1.0.0"
}
```

## Example

```php
$table = new Dejurin\PHPTableGenerate();

$table->set_heading('Name', 'Color', 'Size');

$data = [
    [
        [
            'data' => 'Euro',
            'td' => 'th',
            'scope' => 'row',
            'class' => 'style'
        ], 
        '#000000', 
        100,
    ],
];

echo $table->generate($data);

/* 
<table border="0" cellpadding="4" cellspacing="0">
<thead>
<tr>
<th>Name</th><th>Color</th><th>Size</th></tr>
</thead>
<tbody>
<tr>
<th class="style" scope="row">Euro</th><td>#000000</td><td>100</td></tr>
</tbody>
</table>
*/

```

### License ###
This source code is distributed under [MIT](https://choosealicense.com/licenses/mit/) license.
___

## Sponsors ##
### https://currencyconvert.online/ ###
