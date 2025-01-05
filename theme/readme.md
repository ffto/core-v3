# H9 Boilerplate

## Setup

1. Copy the \_boilerplate folder to the wp-content/themes
2. Move the files in \_boilerplate/\_wp to \_boilerplate
3. Delete the \_boilerplate/\_site folder
4. Open the terminal to use NPM. First do "ncu" to update the packages (if there's packages to update, follow the steps suggested)
5. Install the NPM packages: "npm install" in the terminal
6. Setup the project:
    1. In **grunt.js**, update the variables if needed:
        - IS_WP
        - JS_COMPONENTS
        - FILE_PREFIX
        - NAMESPACE_FUNCTION
        - NAMESPACE_CLASS
        - FILES
    2. In **assets/style/src/style.scss**, update the variables:
        - {{ PROJECT_NAME }}
        - {{ PROJECT_AUTHOR }}
    3. In **functions.php**, update the properties in **h9_wp_setup**:
        - domain
        - menus
        - ...
    4. Search for **@SETUP** in the code to change some variables
7. Call "grunt" in the terminal and everything should work. If you have access to the CORE (with all the JS/Sass/PHP), you can call  
    "grunt --core" or  
    "grunt --core=5" where the numebr value is the depth we need to backtrack to access the "_core" folder
