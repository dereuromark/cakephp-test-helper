# TestHelper Plugin Documentation

## Configuration
- TestHelper.command: If you need a custom phpunit command to run with. 
Both `php phpunit.phar` and `vendor/bin/phpunit` work out of the box.

### Your own template
The default template ships with bootstrap (3) and fontawesome icons.
You can switch out the view templates with your own on project level as documented in the CakePHP docs.

Overwrite the `test_cases` element if you want to support e.g. foundation and their modals.


## Troubleshooting

### Generated code coverage is black&white 
If the assets don't work, make sure your Nginx/Apache (like CakeBox Vagrant VM by default) doesn't block hidden files.

In your /sites-available/ configuration find and remove the following for your local development:

    # deny access to hidden
    location ~ /\. {
        deny all;
    }

Afterwards your coverage should be colorful.
