# Doctrine JMS Json Type
Persist your Value Objects with Doctrine using JMS Serializer.
The DBAL Type supports anything that can be handled by JMS Serializer (scalar, array, Doctrine Collections, DateTime, etc).

# Installation

Add ***"webit/doctrine-jms-json": "^2.0.0"*** to the require section of your ***composer.json***

```json
{
    "require": {
        "webit/doctrine-jms-json": "^2.0.0"
    }
}
```

# Usage

Configure register new Doctrine DBAL type and configure the Serializer:

```php
# boostrap.php

use Doctrine\DBAL\Types\Type;
use Webit\DoctrineJmsJson\DBAL\JmsJsonType;
use JMS\Serializer\SerializerBuilder;
use Webit\DoctrineJmsJson\Serializer\Type\DefaultSerializerTypeResolver;

Type::addType('jms_json', 'Webit\DoctrineJmsJson\DBAL\JmsJsonType');

$serializer = SerializerBuilder::create()->build();
// initialize JmsJsonType
JmsJsonType::initialize($serializer, new DefaultSerializerTypeResolver());

```

Now you can use "jms_json" in the Doctrine field mapping as a type.  

See full configuration of JMS Serializer [here](http://jmsyst.com/libs/serializer)
See full documentation of Doctrine Mapping [here](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/basic-mapping.html#property-mapping)
See Doctrine Custom Types documentation [here](http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/advanced-field-value-conversion-using-custom-mapping-types.html)

# Example

"jms_json" DBAL type can be very useful to store [Value Objects](https://en.wikipedia.org/wiki/Value_object) or objects that structures may vary. 

Consider example of a **slider** on a web page.
Let class ***BannerSet*** contain the ***Slider*** configuration.

```php
interface Slider
{
}

class BannerSet
{
    private $id;
    
    /** Slider */
    private $slider;
    
    /**
     * @return Slider
     */
    public function slider()
    {
        return $this->slider;
    }
    
    /**
     * @param Slider $slider
     */
    public function changeSlider(Slider $slider)
    {
        $this->slider = $slider;
    }
}
```

There are plenty of different JavaScript slider configurations so we don't know in advance the Slider structure.
Let's introduce two implementations:

```
use JMS\Serializer\Annotation as JMS;

class Slider1 implements Slider
{
    /**
     * @JMS\Type("string")
     */
    private $theme;
    
    /**
     * @JMS\Type("double")
     */
    private $slideTime;
    
    // ...
}

class Slider2 implements Slider
{ 
    /**
     * @JMS\Type("array<string>")
     */
    private $effects;

    /**
     * @JMS\Type("boolean")
     */
    private $stopOnHover;
    
    /**
     * @JMS\Type("integer")
     */
    private $pauseTime;
 
    // ...
}
```

To persist the ***BannerSet*** in the database using Doctrine ORM we need to provide the mapping.
Using the standard relational approach we need to map ***BannerSet*** and every ***Slider*** (probably using [single table inheritance]).
It means, we'll have two tables in the database "banner_set" and "slider" with one-to-one relationship between them - quite complex
for storing slider configuration. The more, this approach requires to store ***Slider*** instances like Entities (we have to introduce ID to store in the table),
what breaks our model.

Slider configuration is just object we need to store, but we don't need to query for it. So it's much simpler to store it in a JSON format
in one column of "banner_set" table.

***BannerSet*** Doctrine mapping file would look like:

```yaml
entity:
    class: BannerSet
    id:
        id:
            type: int
    fields:
        slider:
            type: jms_json
```

And that's it!

Obviously, we need to provide the ***Serializer*** mapping for ***Slider1*** and ***Slider2*** 
(what can be done in different ways, eg. via Annotations - check the [JMSSerializer documentation](http://jmsyst.com/libs/serializer) for more details).

# Symfony 2/3 integration

See [WebitDoctrineJsonBundle](https://github.com/dbojdo/Doctrine-JSON-Bundle) Symfony 2/3 integration.

# Tests

    composer install
    ./vendor/bin/phpunit
