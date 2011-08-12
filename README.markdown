# Preview URL Field
 
* Version: 1.0
* Author: [Nick Ryall](http://randb.com.au)
* Build Date: 2011-08-12
* Requirements: Symphony 2.2

## Installation
 
1. Upload the 'preview_url_field' folder in this archive to your Symphony 'extensions' folder.
2. Enable it by selecting the "Field: Entry URL", choose Enable from the with-selected menu, then click Apply.
3. The field will be available in the list when creating a Section.

## Usage

When adding this field to a section, the following options are available to you:

* **Anchor URL** is the URL of your entry view page on the frontend. An `<entry id="123">...</entry>` nodeset is provided from which you can grab field values, just as you would from a datasource. For example:

		/news/entry/{@id}/

* **Open links in a new window** enforces the hyperlink to spawn a new tab/window
* **Hide this field on publish page** hides the hyperlink in the entry edit form

**To enable the Preview link you need to attach the field to the datasource which corresponds with the Anchor URL.**

## Credits

This extension is a modification of Nick Dunn's Entry URL field extension. Thanks Nick :)