# Template Loader Library

The Template Loader library provides a simple and flexible way to parse and render templates in PHP. It supports template inheritance, caching, parameter setting, and looping through arrays and objects. The library is designed to be lightweight and efficient, making it a valuable tool for dynamic content generation.

### Features
- **Template Inheritance:** Allows templates to extend other templates, enabling reusable layouts.
- **Template Caching:** Improves performance by caching rendered templates.
- **Parameter Setting:** Easily set and retrieve parameters within templates.
- **Looping Through Arrays and Objects:** Enables looping through data structures in templates.
- **Error and Warning Handling:** Controls the display of errors and warnings.
- **Logging with PSR-3 Support:** Integrates with PSR-3 compliant logging libraries.
- **Conditional Statements:** Renders content base on conditions.

## Getting Started

### Including the Template Loader File

To use the library, include the `template_loader.php` file in your project:

```php
require_once 'template_loader.php';
```

### Loading the Loader Class

Create an instance of the `template\Loader` class:

```php
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

$template = new template\Loader("template_name", new NullLogger());
```

- **`template_name`:** The name of the template file to load.
- **`logger`:** Optional. An instance of `LoggerInterface` for logging. The default is `NullLogger`, which means no logging.

### Setting Up Template Parameters

#### Template Folder

Set the folder where templates are located:

```php
$template->setTemplateFolder("templates_folder");
```

#### Maximum Template Includes

Set the maximum number of template includes allowed per template:

```php
$template->setMaxTemplateIncludes(10);
```

#### Enabling Caching

Enable template caching with optional max store age (in hours):

```php
$template->enableCaching(true, 24);
```

### Setting Template Parameters

Set parameters in the template using the `set` method:

```php
$template->set("parameter_name", "parameter_value");
```

In the template, retrieve the parameter using:

```html
@{parameter_name}
```

### Looping Through Data

#### Associative Array, Multidimensional Associative Array, Objects

For associative arrays, multidimensional arrays, or objects, use the `forEach` method:

```php
$template->forEach("identifier", $array_or_object);
```

In the template, use the following syntax to loop through and render:

```html
@forEach[identifier]
{{array_key}}
@end[identifier]
```

#### Index Array

For indexed arrays, use the `for` method:

```php
$template->for("array_identifier", $index_array);
```

In the template, use the following syntax to loop through and render:

```html
@for[array_identifier]
{{value}}
@end[array_identifier]
```

### Template Inheritance

#### Extending a Base Template

To extend a base template, use the `extend` method:

```php
$template->extend("base_template_name");
```

In the child template, use `@block` to define blocks:

```html
@extend[base_template]

@block[block_name]
<!-- block content -->
@end[block_name]
```

### Controlling Error and Warning Display

Specify whether to show errors and warnings using the following properties:

```php
$template->show_errors = true; // Default is false
$template->show_warnings = true; // Default is false
```

### Rendering the Template

After setting parameters and defining loops, render the template:

```php
$template->render(true); // Default is true (keep HTML comments)
```

## Examples

### Parsing Parameters in a Template

**parsing_parameter.php**

```php
<?php
$template = new template\Loader("parsing_parameter");
$text = 'Hello World!';
$template->set('text_param', $text);
$template->render();
?>
```

**parsing_parameter.tpl**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Parsing Parameter in Templates</title>
</head>
<body>
    <h1>Parsed parameter is: @{text_param} </h1>
</body>
</html>
```

### Including Other Templates in a Template

**include_template.php**

```php
<?php
$template = new template\Loader("include_template");
$template->render();
?>
```

**include_template.tpl**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Including Templates in Other Templates</title>
</head>
<body>
    @include[header]
</body>
</html>
```

**header.tpl**

```html
<h1>Hello World!</h1>
<h2>I am a header file</h2>
```

### Looping Through Index Array Values in a Template

**index_array.php**

```php
<?php
$template = new template\Loader("index_array");
$week_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$template->for("week_days", $week_days);
$template->render();
?>
```

**index_array.tpl**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Looping and Rendering Index Array Values</title>
</head>
<body>
    <p>The days of the week are:</p>
    <ol>
        @for[week_days]
        <li>{{value}}</li>
        @end[week_days]
    </ol>
</body>
</html>
```

### Looping and Parsing Associative Array Values

**associative_array.php**

```php
<?php
$template = new template\Loader("associative_array");
$user = [
    'username' => 'John Doe',
    'email' => 'johndoe@email.com',
    'location' => 'Big City',
    'about' => 'Hi! I am John Doe, a passionate fullstack web developer.'
];
$template->forEach("user", $user);
$template->render();
?>
```

**associative_array.tpl**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Looping and Rendering Associative Array Values</title>
</head>
<body>
    <p>User Profile:</p>
    <div>
        @forEach[user]
        <div style="width:200px;text-align:center;">
            <p><b>{{username}}</b></p>
        </div>
        <small>
            <ul>
                <li><b>Email:</b> {{email}}</li>
                <li><b>Home:</b> {{location}}</li>
            </ul>
            <p><b>About me:</b><br/> {{about}}</p>
        </small>
        @end[user]
    </div>
</body>
</html>
```

### Looping and Rendering Multidimensional Associative Array Values

**associative_multidimensional_array.php**

```php
<?php
$template = new template\Loader("associative_multidimensional_array");
$students = [
    ['id' => 1, 'name' => 'John Doe', 'age' => 2, 'class' => 'Basic 1'],
    ['id' => 2, 'name' => Mr Henen', 'age' => 4, 'class' => 'Basic 5'],
    ['id' => 3, 'name' => 'James Gosling', 'age' => 1, 'class' => 'Basic 1'],
    ['id' => 4, 'name' => 'Jane Doe', 'age' => 3, 'class' => 'Basic 2']
];
$template->forEach("students", $students);
$template->render();
?>
```

**associative_multidimensional_array.tpl**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Looping and Rendering Associative Multidimensional Array Values</title>
</head>
<body>
    <p>List of our students:</p>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Age</th>
            <th>Class</th>
        </tr>

        @forEach[students]
        <tr>
            <td>{{id}}</td>
            <td>{{name}}</td>
            <td>{{age}}</td>
            <td>{{class}}</td>
        </tr>
        @end[students]

    </table>
</body>
</html>
```

### Looping and Parsing Object Property/Values

**object.php**

```php
<?php
$template = new template\Loader("object");
class User {
    public $username;
    public $email;
    public $location;
    function __construct($username, $email, $location) {
        $this->username = $username;
        $this->email = $email;
        $this->location = $location;
    }
}
$user = new User('Jane Doe', 'janedoe@email.com', 'Small City');
$template->forEach("user", $user);
$template->render();
?>
```

**object.tpl**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Looping and Rendering Object Properties</title>
</head>
<body>
    <p>User Profile:</p>
    <div>
        @forEach[user]
        <div style="width:200px;text-align:center;">
            <p><b>{{username}}</b></p>
        </div>
        <small>
            <ul>
                <li><b>Email:</b> {{email}}</li>
                <li

><b>Home:</b> {{location}}</li>
            </ul>
        </small>
        @end[user]
    </div>
</body>
</html>
```




## Conditional Statements

The Template Loader library supports conditional statements within your templates. This allows you to control the rendering of specific blocks of content based on boolean expressions.

### Syntax

To use conditional statements, you can use the following syntax in your template files:

#### If Statement

```plaintext
@if[identifier](expr)
    <!-- Content to show if expr is true -->
@end[identifier]
```

#### If-Else Statement

```plaintext
@if[identifier](expr)
    <!-- Content to show if expr is true -->
@else[identifier]
    <!-- Content to show if expr is false -->
@end[identifier]
```

### Example

#### Template Code

```html
@if[showGreeting](expr)
    <p>Hello, welcome to our website!</p>
@else[showGreeting]
    <p>Please log in to continue.</p>
@end[showGreeting]
```

### PHP Usage

To use the conditional statements, you need to set the condition in your PHP code. You can do this using the `if` method of the `Loader` class.

#### PHP Code

```php
$template = new template\Loader('exampleTemplate');

// Set the condition for the identifier 'showGreeting'
$template->if('showGreeting', true);

// Render the template
$template->render();
```

In this example, the template will render the greeting message because the condition `showGreeting` is set to `true`. If you change the condition to `false`, the alternative message will be displayed instead.

```php
$template->if('showGreeting', false);
```

### Notes

- The `identifier` must be a string and unique within the template.
- The `expr` parameter should be a boolean value (`true` or `false`).
- You can use multiple conditional blocks with different identifiers within the same template.

This feature provides a simple and flexible way to control the content displayed to users based on dynamic conditions in your PHP code.

## Contributing

If you have any suggestions or find any issues, please feel free to create a pull request or raise an issue on GitHub.

## License

This project is licensed under the MIT License.
