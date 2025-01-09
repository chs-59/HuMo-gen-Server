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
- HGS uses its own code to easily **secure media files** (pictures, movies, 
documents). See HOWTO below.
- Use of **thumbnails** (create and display) can be switched on/off 
**by family tree**
- Picture **resize on upload** can be **disabled** or maximum resolution 
values can be set. (HGO: allways on, fixed values)
- View/rename thumbnails function displays **all connections** to database (HGO: only 
persons)
- Add function to **delete  files** that are not used in database
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

## HOWTO
This HOWTO will only cover the differences to HGO. For a complete (but a 
bit outdated) guide look [here](https://sourceforge.net/projects/humo-gen/files/HuMo-gen_Manual/2022_06_05%20HuMo-gen_manual.pdf/download).

### Media folder
Default media folder of HuMo-gen Server is _media/_, a subfolder of the main 
directory your HuMo-gen Server files resides. You should not rename or
remove _media/_ but you might want to put your media files someware else.
For example it's wise, to choose seperate media folders for each family tree in your
installation not to mess up with your files. You can do this by creating subfolders within
_media/_ for each tree like _media/tree\_01/_, _media/tree\_02_ and so on.
We will find out how to configure this later on.
 
#### Access restriction to media files
Files in your media folders might hold sensitive data or copyright protected 
material. You probably don't want these files to be readable by world. 
If you are on an Apache server or a compatible one you just have to turn on 
mod_rewrite to be safe. All request to _media/_ will be redirected to a script 
that let only allowed users access the files. This is the 
first option.
If you don't have Apache or mod_rewrite or even want more protection on the files 
you can use a second option: place your files _outside_ the DocumentRoot path of 
your server. 
1. Log into the admin area and go to the settings 
_Family trees-->Pictures/ Create thumbnails_. It will look like this:



