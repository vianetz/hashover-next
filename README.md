HashOver - a PHP comment system for e.g. static websites
===
**HashOver** is a PHP comment system intended as a replacement for services like
Disqus. HashOver is free and open source software, under the
[GNU Affero General Public License](http://www.gnu.org/licenses/agpl.html).
HashOver adds a "comment section" to any website, by placing a few simple lines
of JavaScript or PHP to the source code of any webpage. HashOver is a
self-hosted system and allows completely anonymous comments to be posted, the
only required information is the comment itself.

## Purpose of this fork
This repository is a complete refactoring of the [great original hashover-next](https://github.com/jacobwb/hashover-next) with the following goals:
- Improve application security
- Add application logging
- Provide Docker container
- Implement unit tests

### Main Differences to Original
- PHP 8 compatibility
- Build static JavaScript files to allow using security hashes and better minification
- Dependency management and autoloading with composer
- Uses a router
- Added PSR-3 logger
- Environment variables used as secrets instead of PHP classes
- Separate `public` folder to remove file exposure 
- Removed superglobals
- Used state-of-the-art patterns like dependency injection
- Replaced components with external libraries, e.g. Swiftmailer
- Integrated Latte HTML templating engine
- New theme with minimum styles

## Status of refactoring
Currently the base functionality has been refactored. All reworked code is in `src`.

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

HashOver supports 2 types of modes: PHP or JavaScript.  
Depending on the mode you have to choose one of 2 installation methods.

### JavaScript Mode
1. Install libraries
```shell
composer install --no-dev
```
1. Edit settings in `config/settings.json`
1. Compile static assets with
```shell
composer hashover:build-js
```
1. Integrate the JavaScript snippet into your website within the `<body>` HTML tag:
```html
<script type="text/javascript" src="/static/dist/loader.js" async defer onload='new HashOver("hashover", {settings: {"language": "de_DE"}});'></script>
```

## Development

Docker containers can be run with
```
docker-compose up -d
```

Afterwards the admin interface is available on `http://localhost:8080/admin`.

Information and Documentation
---
[Official HashOver 2.0 Documentation](https://docs.barkdull.org/hashover-v2)
