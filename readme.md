# Teflon

Simple JSON database

### Install

Just create an object of `Teflon` class with path to your store

```php
$teflon = new Teflon ('path/to/store');
```

### Examples

```php
$teflon->create('tablename');
$teflon->drop('tablename');
$teflon->merge(['table1', 'table2'], 'name');
$teflon->getConfig('tablename');
$teflon->truncate('tablename');

$teflon->delete('itemname', 'tablename');
$teflon->exists('name', 'table');
$teflon->exists('name', 'item');
$teflon->get('*', 'itemname', 'tablename');
$teflon->put('data', 'itemname', 'tablename');

$teflon->search('needle', 'tablename');
$teflon->set('data', 'itemname', 'tablename');
```

### License

Teflon is licensed under [MIT License](http://opensource.org/licenses/MIT).
