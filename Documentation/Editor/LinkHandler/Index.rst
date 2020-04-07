.. include:: ../../Includes.txt

.. _editor-linkHandler:

============
Link Handler
============

A dedicated link handler allows you to easily integrate login links to other Auth0 applications. You can follow these steps to
display such an link in the frontend.

rst-class:: bignums-xxl

1. Create a new link record

   First of all, you have to create a new link record. You can just follow :ref:`these <editor-dataType-link>` instructions.

2. Create the link

   You can create links anywhere where the TYPO3 link browser is available. Just create the link as you would do for page links.
   Than switch to the tab *Auth0* in the modal window and navigate in the page tree to your storage folder. When you have reached
   your destination, you can create the link by clicking on a previously created link record.

   .. image:: ../../Images/link-browser.png
      :alt: Link Browser
      :class: with-shadow

3. Secure the link

   .. warning::
      Do not forget to secure your links.

   You can protect the links by sharing your content item / record only with certain frontend user groups. Auth0 roles can easily
   be :ref:`mapped <admin-typoscript-roleMapping>` to TYPO3 usergroups.

   .. image:: ../../Images/group-access.png
      :alt: Frontend Usergroup Restrictions
      :class: with-shadow

4. You're all set

   That's all. 🙂

   .. image:: ../../Images/group-access.png
      :alt: Frontend Usergroup Restrictions
      :class: with-shadow
