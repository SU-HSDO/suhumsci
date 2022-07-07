# HS Mega Menu Usage
1) Install the module as usual under ```/admin/modules```
2) Set permission for accessing the settings page under ```/admin/users/permissions```
3) Access the HumSci Mega Menu Settings page user the Appearance menu or via ```admin/appearance/hs_megamenu```
4) Check the box to enable the new mega menu

# HS Mega Menu Twig Usage
1) In a main menu twig template (such as ```menu--main.html.twig```), use the boolean variable ```use_hs_megamenu``` to determine if the mega menu should be enabled or not.