# Flex Objects Plugin

## About

The **Flex Objects** Plugin is for [Grav CMS](http://github.com/getgrav/grav).  Flex objects allows you to create collections of objects, which can modified by CRUD operations via the admin plugin to easily manage large sets of data that don't fit as simple YAML configuration files, or Grav pages. These objects are defined by blueprint written in YAML and they are rendered by a set of twig files. Additionally both objects and collections can be customized by PHP classes, which allows you to define complex behaviors and relationships between the objects.

![](assets/flex-objects-list.png)

![](assets/flex-objects-edit.png)

![](assets/flex-objects-options.png)

![](assets/flex-objects-compressor.gif)

## Installation

Typically a plugin should be installed via [GPM](http://learn.getgrav.org/advanced/grav-gpm) (Grav Package Manager):

```
$ bin/gpm install flex-objects
```

Alternatively it can be installed via the [Admin Plugin](http://learn.getgrav.org/admin-panel/plugins)

## Sample Data

Once installed you can either create entries manually, or you can copy the sample data set:

```shell
$ cp user/plugins/flex-objects/data/entries.json user/data/flex-objects/contacts.json
```

## Configuration

This plugin works out of the box, but provides several fields that make modifying and extending this plugin easier:

```yaml
enabled: true
built_in_css: true
extra_admin_twig_path: 'theme://admin/templates'
extra_site_twig_path:
directories:
  - blueprints://flex-objects/contacts.yaml
```

Simply edit the **Flex Objects** plugin options in the Admin plugin, or copy the `flex-objects.yaml` default file to your `user/config/plugins/` folder and edit the values there.   Read below for more help on what these fields do and how they can help you modify the plugin.

Most interesting configuration option is `directiories`, which contains list or blueprint files which will define the flex types.

## Displaying

To display the directory simply add the following to our Twig template or even your page content (with Twig processing enabled):

```twig
{% include 'flex-objects/flex-objects.html.twig' %}
```

Alternatively just create a page called `flex-objects.md` or set the template of your existing page to `template: flex-objects`.  This will use the `flex-objects.html.twig` file provided by the plugin.

Please check the provided twig files inside ``

# Modifications

This plugin is configured with a sample contacts directory with a few sample fields:

* published
* first_name
* last_name
* email
* website
* tags

These are probably not the exact fields you might want, so you will probably want to change them. This is pretty simple to do with Flex Objects, you just need to change the **Blueprints** and the **Twig Templates**.  This can be achieved simply enough by copying some current files and modifying them.

Let's assume you simply want to add a new "Phone Number" field to the existing Data and remove the "Tags".  These are the steps you would need to perform:

1. Copy the `blueprints/flex-objects/contacts.yaml` Blueprint file to another location, let's say `user/blueprints/flex-objects/`. The file can really be stored anywhere, but if you are using admin, it is best to keep the blueprint file where admin can automatically find it.

1. Edit the `user/blueprints/flex-objects/contacts.yaml` like so:

    ```yaml
    title: Contacts
    description: Simple contact directory with tags.
    type: flex-objects
    
    config:
      admin:
        list:
          title: name
          fields:
            published:
              width: 8
            name.last:
              link: edit
            name.first:
              link: edit
            company:
            email:
            website:
            tags:
      data:
        storage:
          type: file
          file: user://data/flex-objects/contacts.yaml
    
    form:
      validation: loose
    
      fields:
        published:
          type: toggle
          label: Published
          highlight: 1
          default: 1
          options:
            1: PLUGIN_ADMIN.YES
            0: PLUGIN_ADMIN.NO
          validate:
            type: bool
            required: true
    
        name.last:
          type: text
          label: Last Name
          validate:
            required: true
        name.first:
          type: text
          label: First Name
          validate:
            required: true
    
        company:
          type: text
          label: Company Name
        email:
          type: email
          label: Email Address
          validate:
            required: true
        website:
          type: url
          label: Web Site
    
        address.street1:
          type: text
          label: Address 1
        address.street2:
          type: text
          label: Address 2
        address.city:
          type: text
          label: City
        address.country:
          type: text
          label: Country
        address.state:
          type: text
          label: State or Province
        address.zip:
          type: text
          label: Postal / Zip Code
    
        tags:
          type: selectize
          size: large
          label: Tags
          classes: fancy
          validate:
            type: commalist
    
 
 
 
    title: Flex Objects
    form:
      validation: loose
    
      fields:
        published:
          type: toggle
          label: Published
          highlight: 1
          default: 1
          options:
            1: PLUGIN_ADMIN.YES
            0: PLUGIN_ADMIN.NO
          validate:
            type: bool
            required: true
        last_name:
          type: text
          label: Last Name
          validate:
            required: true
        first_name:
          type: text
          label: First Name
        email:
          type: email
          label: Email
          validate:
            required: true
        website:
          type: text
          label: Website
        tags:
          type: selectize
          size: large
          label: Tags
          classes: fancy
          validate:
            type: commalist
    
        tools_section:
          type: section
          field_classes: overlay bottom
    
    
          fields:
    
            _post_entries_save:
              label: PLUGIN_FLEX_OBJECTS.AFTER_SAVE
              type: save-redirect
              default: create-new

    ```

    Notice how we removed the `tags:` Blueprint field definition, and added a simple text field for `phone:`.  If you have questions about available form fields, [check out the extensive documentation](https://learn.getgrav.org/forms/blueprints/fields-available) on the subject.

1. Now we have to instruct the plugin to use this new blueprint rather then the default one provided with the plugin.  This is simple enough, just edit the **Blueprint File** option in the plugin configuration file `flex-objects.yaml` to point to: `user://data/flex-objects/entries.yaml`, and make sure you save it. This will modify the `entries-edit` form automatically.  

1. Now we need to adjust the `entries-list` form that shows the columns.  To do this, you are going to need to copy the existing `user/plugins/flex-objects/admin/templates/partials/entries-list.html.twig` file to another location that is in the **Twig Paths** <sup>1</sup>. The simplest way to add Twig templates is to simply add them under your theme's `templates/` folder ensuring the folder structure is maintained. Let's assume you are using Antimatter theme (although any theme will work), simply copy the `entries-list.html.twig` file to `user/themes/antimatter/admin/templates/partials/entries-list.html.twig` (you will have to create these folders as `admin/` doesn't exist under themes usually) and edit it.

    The first part to edit is the column headers, let's replace the `Tags` header with `Phone`

    ```twig
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Website</th>
                <th>Phone</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
    ```

    Next you simply need to edit the actual column, replacing the `entry.tags` output with:

    ```twig
        <td>
            {{ entry.phone }}
        </td>
    ```
    
    This will ensure the backend now lets you edit and list the new "Phone" field, but now we have to fix the frontend to render it.

1. We need to copy the frontend Twig file and modify it to add the new "Phone" field.  By default your theme already has its `templates`, so we can take advantage of it <sup>2</sup>. We'll simply copy the `user/plugins/flex-objects/templates/flex-objects/site.html.twig` file to `user/themes/antimatter/templates/flex-objects/site.html.twig`. Notice, there is no reference to `admin/` here, this is site template, not an admin one.

1. Edit the `site.html.twig` file you just copied so it has these modifications:

    ```twig
        <li>
            <div class="entry-details">
                {% if entry.website %}
                    <a href="{{ entry.website }}"><span class="name">{{ entry.last_name }}, {{ entry.first_name }}</span></a>
                {% else %}
                    <span class="name">{{ entry.last_name }}, {{ entry.first_name }}</span>
                {% endif %}
                {% if entry.email %}
                    <p><a href="mailto:{{ entry.email }}" class="email">{{ entry.email }}</a></p>
                {% endif %}
                {% if entry.phone %}
                    <p class="phone">{{ entry.phone }}</p>
                {% endif %}
            </div>
        </li>
    ```

    And also the JavaScript initialization which provides which hooks up certain classes to the search:
    
    ```html
    <script>
        var options = {
            valueNames: [ 'name', 'email', 'website', 'phone' ]
        };
    
        var userList = new List('flex-objects', options);
    </script>
    ```

    Notice, we removed the `entry-extra` DIV, and added a new `if` block with the Twig code to display the phone number if set.

# File Upload

To upload files you can use the `file` form field.  []The standard features apply](https://learn.getgrav.org/forms/blueprints/how-to-add-file-upload), and you can simply edit your custom blueprint with a field definition similar to:

```
    item_image:
      type: file
      label: Item Image
      random_name: true
      destination: 'user/data/flex-objects/files'
      multiple: true
```

# Advanced

You can radically alter the structure of the `entries.json` data file by making major edits to the `entries.yaml` blueprint file.  However, it's best to start with an empty `entries.json` if you are making wholesale changes or you will have data conflicts.  Best to create your blueprint first.  Reloading a **New Entry** until the form looks correct, then try saving, and check to make sure the stored `user/data/flex-objects/entries.json` file looks correct.

Then you will need to make more widespread changes to the admin and site Twig templates.  You might need to adjust the number of columns and the field names.  You will also need to pay attention to the JavaScript initialization in each template.

### Notes:

1. You can actually use pretty much any folder under the `user/` folder of Grav. Simply edit the **Extra Admin Twig Path** option in the `flex-objects.yaml` file.  It defaults to `theme://admin/templates` which means it uses the default theme's `admin/templates/` folder if it exists.
2. You can use any path for front end Twig templates also, if you don't want to put them in your theme, you can add an entry in the **Extra Site Twig Path** option of the `flex-objects.yaml` configuration and point to another location.
