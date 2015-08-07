# php-static-reflection
Implementation of Reflection API that parses PHP source files. This allows reflection without loading a class.
Since PHP has no way of unloading classes this is useful to reduce memory footprint when you need to inspect
a class but don't need to load it, for example extracting meta data.

Below is a benchmark comparing Reflection to StaticReflection on drupal 8 test classes:

| Metric         | Reflection | StaticReflection |
| -------------- | ---------: | ---------------: |
| CPU Time       | 0.38s      | 3.92s            |
| Peak Memory    | 46Mb       | 9.2Mb            |
| Function Calls | 62,595     | 794,726          |

Resolving constants, properties, and methods requires looking at parent class, used traits and implemented interfaces.
This resolving is done as need (ie. lazy loaded) and therefore by default reflection objects are cached. So its
possible to reduce the memory usage even further by unloading reflection objects from cache that won't be needed by
another class after its processed without affecting performance. For example, in the above benchmark after extracting
information from the test class it could be unloaded.

StaticReflection does not implement all of the Reflection API, but it aims to implement anything that can be done
statically. As such it implements more of the API then other libraries like
[Doctrine Common](https://github.com/doctrine/common/tree/master/lib/Doctrine/Common/Reflection).
