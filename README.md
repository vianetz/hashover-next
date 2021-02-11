HashOver
===
**HashOver** is a PHP comment system intended as a replacement for services like
Disqus. HashOver is free and open source software, under the
[GNU Affero General Public License](http://www.gnu.org/licenses/agpl.html).
HashOver adds a "comment section" to any website, by placing a few simple lines
of JavaScript or PHP to the source code of any webpage. HashOver is a
self-hosted system and allows completely anonymous comments to be posted, the
only required information is the comment itself.

## Purpose of this fork
This repository is a modern implementation of the [great original hashover-next](https://github.com/jacobwb/hashover-next) with the following goals:
- PHP 8 compatibility
- Dependency management with composer
- Replace components with external libraries, e.g. Swiftmailer
- Add application logging
- Improve application security (by using a router, environment variables as secrets, adding separate `htdocs` folder, removing superglobals, dependency injection, etc.)
- Provide Docker container
- Implement unit tests

Notable Features
---
General                          | Customization           | Advanced
-------------------------------- | ----------------------- | --------------------------------
Threaded replies                 | Multiple themes         | Allows limited use of HTML
Comment editing & deletion       | Customizable HTML       | Multiple comment sorting methods
Likes & Dislikes                 | Comment layout template | Spam filtering
Popular comments section         | Customizable CSS        | Notification emails
Multiple languages               | File format plugins     | Comment RSS feeds
Automatic URL links              | Authentication plugins  | Referrer checking
Administration                   |                         | Comment permalinks
Avatar icons                     |                         | IP address blocking
Display remote images            |                         |


## Installation

```
composer install
# Do all settings and then execute
composer build-js
```


Information and Documentation
---
[Official HashOver 2.0 Documentation](https://docs.barkdull.org/hashover-v2)
