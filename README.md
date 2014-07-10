# Course Overview Lite

## What does it do?

The Course Overview Lite block is faster and lighter replacement for the default Course Overview block.
This block will attempt to first load a course list from the internal moodle navigation followed by a redundant
fail-over of ajax course loading and finally falling back to the default behaviour.

## Feature List

* Ajax course detailed overview
* Active course highlighting
* Users can hide specific courses
* Users can reorder the course listing
* Customizable alert area
* Toggle simplified and detailed course views
* Improved page load speed

## What is all this code?

This repository contains a copy of Moodle 2.6.2, along with the
Course Overview Lite block plugin. The plugin itself is contained entirely
within the blocks/course\_overview\_lite/ directory.

## Tested with

* Moodle 2.4
* Moodle 2.6

## Repositories

Moodle core + plugin:
https://github.com/ualberta-eclass/moodle-block_course_overview_lite

Plugin only:
https://moodle.org/plugins/view.php?plugin=block_course_overview_lite

Based on:
Course overview (block_course_overview) Copyright 2012 Adam Olley <adam.olley@netspot.com.au>
eClasss course overview (block_eclass_course_overview) Copyright 2013 Dom Royko <droyko@ualberta.ca>

## Installation

### Method 1 - zip

1. Download and unzip from moodle plugin site:
https://moodle.org/plugins/view.php?plugin=block_course_overview_lite
2. Copy blocks/course\_overview\_lite/ folder into your moodleinstall/blocks/.
3. On your Moodle site, browse to:
My home / Site administration / Notifications
4. Install the new plugin.

### Method 2 - git integration

Use if your moodle installation is under git control.

1. Add github repository as new remote:
git remote add overviewlite git://github.com/ualberta-eclass/moodle-block_course_overview_lite.git
2. git fetch
3. checkout your deployment branch.
4. merge from the Moodle version branch matching your development branch base.
5. Install the new plugin from the Moodle admin Notification page as above.
