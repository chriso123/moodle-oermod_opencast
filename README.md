### moodle-oermod_opencast

Subplugin for [local_oer](https://github.com/llttugraz/moodle-local_oer) plugin.<br>
This subplugin loads video metadata from [Opencast](https://opencast.org/) to 
be released as OER resource (Open Educational Resources).


## Table of Contents


- [Installation](#installation)
- [Requirements](#requirements)
- [Features](#features)
- [Configuration](#configuration)
- [Usage](#usage)
- [Dependencies](#dependencies)
- [API Documentation](#api-documentation)
- [Subplugins](#subplugins)
- [Privacy](#privacy)
- [Known Issues](#known-issues)
- [License](#license)
- [Contributors](#contributors)

## Installation

There are 3 options on how to install this subplugin.

1. Download the subplugin and extract the files.
2. Move the extracted folder to your `moodle/local/oer/modules` directory.
3. Log in as an admin in Moodle and navigate to `Site Administration` or `Dashboard`.
4. Follow the on-screen instructions to complete the installation.

or

1. Download the subplugin.
2. Log in as admin in Moodle and navigate to `Site Administration > Plugins > Install plugins`.
3. Add zip directory to drag and drop field.
4. Follow the on-screen instructions to complete the installation.

or

1. Open `.../moodle/local/oer/modules` in a terminal.
2. Add this subplugin in terminal with `git clone <ssh link> <folder name>`.
3. Log in as admin and navigate to `Site Administration` or `Dashboard`.
4. Follow the on-screen instructions to complete the installation.

with the third option there are also the git functions available.


## Requirements

- Supported Moodle Version: 4.5
- Supported PHP Version:    8.1 - 8.3
- Supported Databases:      MariaDB, PostgreSQL
- Supported Moodle Themes:  Boost

This plugin has only been tested under the above system requirements against the specified versions.
However, it may work under other system requirements and versions as well.

## Features

This plugin loads video metadata from [Opencast](https://opencast.org/) to be released as OER resource (Open Educational Resources).
It uses block_opencast to get the series attached to Moodle courses and the API from tool_opencast to load the video metadata.

When a video is set to OER it also stores changes in metadata back to the video (license). 
The access rules for the Opencast video are changed, so that it is not possible to easily delete a video.
Information is added to the `block_opencast` view that a video has been released and how to delete it.

### On OER release

The released videos remain in Opencast and the link is provided in the release metadata.
The video will be set to public.

## Configuration

The following setting is available (by default enabled):

- Add the users and their roles automatically to the OER element when loading the videos from opencast. Users will only be added when the element metadata has not been edited and stored. More users can be added manually and also the automatically added people can be removed.
Following roles will be added: Presenter, Contributor, Rightsholder.

## Usage

See description of main plugin [local_oer](https://github.com/llttugraz/moodle-local_oer).

## Dependencies

* [local_oer](https://github.com/llttugraz/moodle-local_oer) v2.3.0+
* [tool_opencast](https://moodle.org/plugins/tool_opencast) v4.3-r1+
* [block_opencast](https://moodle.org/plugins/block_opencast) v4.3-r1+

## API Documentation

Uses the API of `tool_opencast` to communicate with Opencast instances.

## Subplugins

No subplugins.

## Privacy

No personal data are stored.

## Known Issues

* Support for multiple Opencast instances, connected to one Moodle instance, has to been implemented yet.
* Unit tests for `class module` have not been implemented yet.

## Accessibility Status

No accessibility status yet. TODO.

## License

This plugin is licensed under the [GNU GPL v3](https://www.gnu.org/licenses/gpl-3.0.html).

## Contributors

- **Ortner, Christian** - Developer - [GitHub](https://github.com/chriso123)
