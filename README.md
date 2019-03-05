[![Build Status](https://travis-ci.org/drupal-composer/info-rewrite.svg?branch=master)](https://travis-ci.org/drupal-composer/info-rewrite)

A composer plugin for rewriting Drupal `.info`/`.info.yml` files to include version information that Drupal requires.

This is only required when downloading projects that aren't full releases (for instance, dev versions).

[![Latest Stable Version](https://poser.pugx.org/drupal-composer/info-rewrite/v/stable.svg)](https://packagist.org/packages/drupal-composer/info-rewrite)
[![Total Downloads](https://poser.pugx.org/drupal-composer/info-rewrite/downloads.svg)](https://packagist.org/packages/drupal-composer/info-rewrite)
[![Latest Unstable Version](https://poser.pugx.org/drupal-composer/info-rewrite/v/unstable.svg)](https://packagist.org/packages/drupal-composer/info-rewrite)
[![License](https://poser.pugx.org/drupal-composer/info-rewrite/license.svg)](https://packagist.org/packages/drupal-composer/info-rewrite)

## Installation

`composer require drupal-composer/info-rewrite:~1.0`

## Configuration

By default, this plugin only acts on packages of type `drupal-core`, `drupal-module`, `drupal-profile`, and `drupal-theme` (see DrupalInfo::$packageTypes). You can add additional package types in your `composer.json` file. Add to the `config` array an entry with key `drupal-info-rewrite--additional-packageTypes` and make its value be an array of strings which are the additional types you would like to add.
