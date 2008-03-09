NOTE: This module is currently in an alpha state, and many aspects of it remain 
in the proof-of-concept realm. Please do not use it for any production purposes 
until such a time as a 1.0 is released.

The deployment framework is a series of modules which are designed to allow 
developers to easily stage Drupal data from one site to another. It is designed 
to have three parts that work together:

* Deployment API - This implements the concept of a deployment plan. You create 
a deployment plan and add objects to it which will be deployed (content types, 
views, etc.) When the time comes these items are pushed to a server you specify 
via XMLRPC with an API key. The data stored about each item is extremely minimal, 
relying largely on the implementers to implement object-specific knowledge. 
Currently this is deploy.module

* Deployment Implementers - Individual modules implement the deployment API to 
add the data they need to a deployment plan, and expose that ability to the front 
end. Currently I have content_copy_deploy.module and system_settings_deploy.module.

* Deployment Services - Services modules which contain the knowledge to receive 
deployed data and do what is appropriate on the destination server. Currently I 
have content_copy_service.module and system_settings_service.module. 

Installation instructions can be found in INSTALL.txt

USAGE
-----
In order for deployments to work, your source server must be able to reach your 
destination server on port 80. 

1) Surf to admin/deploy and click "Add a new deployment plan". All a deployment 
plan needs is a name by which you can identify it. Enter that name and submit 
the form. You should be returned to the deployment plan listing with your plan listed.

2) Surf to admin/deploy/servers and click "Add a new server". You will need to 
enter a name for the server, a path to the destination servers XMLRPC Server 
implementation (usually http://<yoursite>/services/xmlrpc) and the destination 
server's API Key as created in the installation instructions. Submit the form.

3a) If you have enabled Content Copy Deployment - Surf to /admin/content/types. 
Click the "Deploy" tab. Choose a deployment plan and content type you want to 
export (this should be a content type that does not exist on the destination 
server). Submit the form.

3b) If you have enabled System Settings Deployment - Surf to a page that 
implements system_settings_form (example: admin/settings/date-time). At the bottom 
of the form you can choose a deployment plan. Choose one and submit the form. 

4) Return to admin/deploy and click the name of your deployment plan. You should 
see the items above listed. 

5) Now you can click "Push this plan live"

6) In theory you should now be able to go to your destination server and your 
settings/content types should appear there.

CAVEATS
-------
- Content types are currently hardcode only to create on the destination server, 
not update. Therefore is a content type of the same name exists on the destination 
server, the push of this type will fail.

- The appropriate modules must be enabled (for instance, a content type push will 
fail if Content Copy is not enabled on the destination server)

- If the Drupal/module versions differ between the source and destination servers, 
system settings may not transfer properly.

- Content Copy has some bugs which prevent some content types from being imported 
properly. These content types will also fail when being deployed. If you can Export 
and then Import a content type, then it will work here too.