# NOTICE

- This repo is only for maintenance of the Mabrex core code
- It is not an active project, but the base starting code for any project. It represents the latest Mabrex code
  available for use in Rahisi Solution projects
- This means that whenever a new project is initiated, it should take its starting code form this repo and not elsewhere
- With the above points in mind, under no circumstances should modules specific for another project be added in here or
  in its respective database
- Any addition of a feature, fix or any upgrade should be clearly explained in the commit message and agreed upon by all
  backend developers before adding
- The first couple of commits may deviate from the previous notice, as they will solely be focused on migrating core
  Mabrex code to the repo
- As usual, never commit app-variables.js, logs directories, .env/.mx3 files, composer.lock, vendor

# NOTES

## Available Functionalities

1. login and auth
2. ...
3. ...

## Ongoing Functionalities

1. auth, including login, reset password and change password
2. user CRPESA, including Dual Control form YakoAccount during user creation
2. ...
3. ...

## Upcoming Functionalities

1. dashboard
2. report
3. utility menus
4. ...

## Suggested Functionalities

1. ...
2. ...
3. ...

### crazy ideas to include

1. iwepo module module moja kwa kila distinct functionality
2. CRPESA only: department
3. CRPESA with PB and AR: department
4. attachment upload and display
5. onboarding using ZANID -
6. form to submit checkboxes -
7. form to Add Item (such as kwenye ZFF)
8. payment setup
9. invoice, payment, receipt
10. report
11. various UIs za profile: OVC, Business profile from ZCT, Seafarer from ZMA, MedicalApplication from ZMA, Insurance
    module from VisitZanzibar for show of Benefactor/Beneficiary
12. see if Utility nzima inafanya kazi, especially Database Backup


## deployment
- ongea na Nidhin aweke backend na API sehemu tofauti to escape the issues with attachment display