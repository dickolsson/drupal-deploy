Drupal Deploy 8.x
=================

-[![Build Status]
-  (https://travis-ci.org/dickolsson/drupal-deploy.svg?branch=8.x-0.x)
-]
-(https://travis-ci.org/dickolsson/drupal-deploy)


CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Permissions
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Deploy - Content Staging module  is designed to allow users to easily stage
and preview content for a Drupal site. Deploy automatically manages dependencies
between entities (like node references). It is designed to have a rich API which
can be easily extended to be used in a variety of content staging situations.

Deploy has been re-designed for Drupal 8 and is based on the Multiversion an
Replication modules. This creates a very flexible and efficient content staging
framework for Drupal 8! See the below presentation for more details:

 * DrupalCon Dublin 2016 - https://www.youtube.com/watch?v=tgmypdEOEVs

 * For a full description of the module visit:
   https://www.drupal.org/project/deploy
   or
   https://www.drupal.org/docs/8/modules/deploy

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/deploy


REQUIREMENTS
------------

This module requires the following modules outside of Drupal core:

 * Multiversion - https://www.drupal.org/project/multiversion
 * Key-value Extensions - https://www.drupal.org/project/key_value
 * Conflict - https://www.drupal.org/project/conflict


RECOMMENDED MODULES
-------------------

To deploy content between two different sites the RELAXed Web Services module
should be enabled and configured.

 * RELAXed Web Services - https://www.drupal.org/project/relaxed

INSTALLATION
------------

 * Install the Deploy - Content Staging module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


PERMISSIONS
-----------

Administer deployments:
These permissions are needed to access the /admin/structure/deployment path,
which lists all of the deployments that have taken place.

Deploy to any workspace:
To be able to perform a deployment to any workspace (or remote target).

Deploy to own workspace:
To be able to perform a deployment to any local workspace created by the current
user.

Deploy to <workspace>:
A permission is added for each workspace or remote target. For example a remote
site "Editorial" with a workspace "Live" would create the permission "Deploy to
Editorial:Live". A local workspace "Spring Campaign" would create the permission
"Deploy to Spring Campaign".


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Deploy module doesn't provide any settings or configuration pages.
    3. The user should make sure that target workspaces and/or remotes are
       configured correctly (workspaces configuration pages are provided by
       Workspace module).

Drupal to Drupal deployment between two (or more) sites:
    1. Either go to "Structure" then "Deployment" or use the deploy link in the
       top right of the toolbar to get to the deployment page.
    2. Visit the external site and you will see all the content from the initial
       site.


MAINTAINERS
-----------

 * Andrei Jechiu (jeqq) - https://www.drupal.org/u/jeqq
 * Tim Millwood (timmillwood) - https://www.drupal.org/u/timmillwood
