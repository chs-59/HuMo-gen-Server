# HuMo-gen Server

>
> HuMo-gen Server is free and open-source genealogy software.
>
> It's a fork of the software project HuMo-genealogy, developed and 
> maintained by Huub Mons and others for more than two decades. You will find
> all information [here](https://github.com/HuubMons/HuMo-genealogy/).
 
The "Server" version puts its focus on a reliable code base that 
can be used out in the World Wide Web as a stable and secure platform 
on hosting environments running Linux machines with Apache and/or Nginx. 
HuMo-gen Server is not tested on Windows, nor on iOS or any Docker solutions.  
The code base is 6.7.9a of HuMo-genealogy extended by usefull new 
features and a bunch of code and interface cleanups along with bug fixes. 
You will find a list of differences below.

Berlin, January 2025
Christian Seel

## Differences HuMo-gen Server (HGS) to original HuMo-genealogy (HGO)
- HuMo-gen-Server uses its own code to easily **secure media files** (pictures,
movies, documents). See HOWTO below. (HGO: lot of bugs and security issues)
- Use of **thumbnails** (create and display) can be switched on/off 
**by family tree** (HGO: feature not implemented)
- Picture **resize on upload** can be **disabled** or maximum resolution 
values can be set. (HGO: allways on, fixed values)
- View/rename thumbnails function displays **all connections** to database (HGO: only 
persons)
- Add function to **delete files** that are not used in database (HGO: feature 
not available)
- **Categories** are widely **rebuild**: 
    - Free category name up to 30 characters (HGO: 2 character filename suffix 
or folder)
    - Free amout of categories selectable (HGO: one Category per file)
    - Three default categories: persons, families, sources (HGO: persons only)
    - Categories set up by tree (HGO: global)
    - Category selection for media files added to data editors of person,
 family and source (HGO: Category not changeable)
    - Category selection can be exported and importet via GEDCOM files (using 
program specific tag)
- **GLightbox updated** to 3.20
- Player **plyr.io reintegrated to lightroom gallery** for mp4 and webm. (HGO: 
Player not used, playback depends on browser capabilities)
- Player plyr.io **completely localized** (no links to CDN)

## Removed or cleanup
- Global option to _enable automatic thumbnail generation_ removed. Reason: Thumbnail
settings moved to tree pictures settings.
- Detection of image libraries removed from admin start page
- Picture path can only be set at one place in backend (HGO: four places). 
- Function _create all thumbnails_ removed. Reason: Timeout problems on large 
picture galleries
- Fixed inconsistences produced by category suffix
- Fixed bug when renaming files in subfolder

## HOWTO (in progress) 
This HOWTO will only cover the differences to HGO. For all other topics
look [here](https://sourceforge.net/projects/humo-gen/files/HuMo-gen_Manual/2022_06_05%20HuMo-gen_manual.pdf/download).

### Media folder
Default media folder of HuMo-gen Server is _media/_, a subfolder of the main 
directory your HuMo-gen Server files resides. You should not rename or
remove _media/_ but you might want to put your media files someware else.
For example it's wise, to choose seperate media folders for each family tree in your
installation not to mess up with your files. You can do this by creating subfolders within
_media/_ for each tree like _media/tree\_01/_, _media/tree\_02_ and so on.
We will find out how to configure this later on.
 
#### Access restriction to media files
Before we set up the media folder(s), we should put some thoughts on security. 
HuMo-genealogy (original and Server) gives you several options to protect data 
in your family tree from unwanted _view_ but will not reject unwanted _access_ 
to your media files right away.
So even if you will not see see a picture of a person with enabled 
privacy settings within HuMo-genealogy, all bad people in the world might be 
able to access the picture by a direct link or just guessing or probing 
the filename. To manage this problem HuMo-gen-Server offers two options:
1. Turn on _mod\_rewrite_ on your server. Then all request to _media/_ will be 
redirected to a script that let only allowed users access the files. If you need
more then one media directory place them all into _media/_. This is 
your first option.
2. But the second option is even better: Place your media folder _outside_ the
DocumentRoot of you server. Example: If your server operates within _/var/www/htdocs_
create a new folder _/var/www/pictures_ and place your media files here. You server
will never be able to access these files by direct links. 

HuMo-gen-Server will tell you in the configuration page where youre DocumentRoot
and your media path are located and if the media path configured is a safe place.

**!!!Important!!!** Be aware that access to media files is configured in _group_
 settings. Especially have a close look at the group _guest_!!! Giving this group
access to pictures will probably conteract your security effords.

#### How to configure the media path
1. Log into the admin area and go to the settings 
_Family trees-->Pictures/ Create thumbnails_. It will look like this:

![pic path settings](images/docu/HuMo-gen_Server_picpath.webp)

(1) tells you if mod_rewrite is switched on.

(2) tells the complete path to your media directory. In this example the 
path is located within _media/_ folder and protected by the rewrite engine

(3) No file redirection is needed in this example because media folder is 
already protected.

This example shows a media path _outside_ DocumentRoot. Therefor a file redirect
is needed and HuMo-gen-Server will use server rewrite (mod_rewrite) for this task.

![pic path settings](images/docu/PicturePathSettingsOutside.png)

