CHANGELOG for Sulu Document Manager
===================================

* dev-master
    * HOTFIX      #118 Set path and node-name after renaming node
    * HOTFIX      #117 Execute rename in flush-event to avoid ItemNotFoundException

* 0.10.0 (2017-06-28)
    * FEATURE     #116 Added set-default-author to metadata

* 0.10.0-RC1 (2017-06-01)
    * BUGFIX      #113 Updated ProxyManager to be compatible with PHP 7
    * BUGFIX      #112 Fixed overwrite of exist created date. 
    * ENHANCEMENT #110 Added node-name-slugifier to centralice additional node name replacer
    * ENHANCEMENT #109 Added metadata to configure remove-live
    * FEATURE     #107 Added recursive restore to allow also versions of children

* 0.9.1 (2017-03-16)
    * ENHANCEMENT #107 Added VersionNotFoundException

* 0.9.0-RC1
    * FEATURE     #105 Fixed changed times for both workspaces
    * FEATURE     #97  Added versioning functionalities
    * ENHANCEMENT #104 Removed phpcr-odm dependency
    * FEATURE     #101 Added metadata form-type
    * BUGFIX      #98  Removed deprecations

* 0.8.3 (2016-12-21)
    * HOTFIX      #100 Leave mix:referencable after publish because of jackrabbit misbehavior

* 0.8.2 (2016-11-24)
    * HOTFIX      #96 Added overwrite option to ExplicitSubscriber

* 0.8.1 (2016-08-08)
    * HOTFIX      #91 Added replacer to avoid 10e in node-names

* 0.8.0 (2016-07-28)
    * FEATURE     #94 Added removeDraft method to DocumentManager

* 0.7.0 (2016-07-21)
    * ENHANCEMENT #93 Use correct default phpcr session
    * FEATURE     #92 Added unpublish method to DocumentManager
    * ENHANCEMENT #89 Added auto_rename option to AutoNameSubscriber
    * ENHANCEMENT #89 Extracted LocalizedTitleBehavior from TitleBehavior
    * BUGFIX      #90 Added missing check in handleChangeParent method of ParentSubscriber
    * BUGFIX      #88 Introduced mandatory locale in document-registry
    * FEATURE     #81 Added publish method to DocumentManager
    * BUGFIX      #85 Fixed get locale for proxy

* 0.6.1 (2016-06-01)
    * HOTFIX      #83 Fixed auto-name subscriber to rename at the very end of persist

* 0.6.0 (2016-04-11)
    * ENHANCEMENT #58 Added behavior to save unlocalized timestamps and added json_array mapping type
    * ENHANCEMENT #59 Removed blame subscriber
    * Added getAllMetadata() method to MetadataFactoryInterface

* 0.5.1 (2015-12-18)
    * FEATURE #56 added auto_name option to deactivate renaming of nodes in certain situations

* 0.5.0 (2015-12-01)
    * BUGFIX #55 Added rehydrate option to stop propagation listener

* 0.4.0 (2015-11-05)

* 0.3.0 (2015-10-28)

* 0.2.2 (2015-07-22)

* 0.2.1 (2015-07-12)

* 0.2.0 (2015-07-01)

* 0.1.0 (2015-06-19)
