Mod Courselinks
==================================
This moodle mod allows to display links to other courses. This mod is displayed in a label.

Goals
------------
This mod goals are to displays in a course links to others courses in order to have a navigation between several courses.

Requirements
------------
- Moodle 3.7 or later.<br/>
  -> Tests on Moodle 3.7 to 3.11.8 (tests on older moodle versions not made yet).<br/>
  -> Tests on Moodle 4 in progress.<br/>
- Use Boost theme or a theme which extends Boost theme (use bootstrap).

Installation
------------
1. Local plugin installation

- With git:
> git clone https://github.com/andurif/moodle-mod_courselinks.git mod/courselinks

- Download way:
> Download the zip from https://github.com/andurif/moodle-mod_courselinks/archive/refs/heads/main.zip, unzip it in mod/ folder and rename it "courselinks" if necessary or install it from the "Install plugin" page if you have the right permissions.

2. Then visit your Admin Notifications page to complete the installation.

Presentation / Features
------------
Display links to others courses according three display types for now:
- Card: links to courses will be display as cards with their own course image.
- List : links to courses will be listed one below the other.
- Navigation menu: links to courses will be displayed on a menu where each course will be a menu item.
<p>Be careful, a link is displayed only for users with access rights to this course (unless you force the display in the form) !<br>
Besides when you want to add a resource only courses you manage will be displayed in the form 
(filter on the moodle/role:assign "Assign roles to users" capability).</p>
<p>You can also choose the way to display to linked course: in a new window, a new tab...</p>

<img src="https://i15.servimg.com/u/f15/17/05/22/27/course10.png" />

Possible improvements
-----
- Add other display types.
- Improve responsive.
- Add setting to setup if we want to use other courses than only courses we manage ?

About us
------
<a href="https://www.uca.fr" target="_blank">Université Clermont Auvergne</a> - 2022.<br/>
