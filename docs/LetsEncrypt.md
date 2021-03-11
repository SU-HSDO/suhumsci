# LetsEncrypt Cert on Acquia Cloud

## Setup
* We are using [acme.sh](https://github.com/Neilpang/acme.sh) to assist in generating the certificates and doing autorenewal.
* ssh into the Acquia server via drush
* Change directory into the home directory where acme.sh will install the necessary files.
* Run `curl https://get.acme.sh | sh` Some of the commands in the install script will fail. It attempts to install an alias and a cron job. Both of which don't work.

At this time we can begin to issue LetsEncrypt certificates.

## Drupal Setup
In this repository we have adopted the LetsEncrypt Challenge module as it has little traction in the Drupal 8 level.
More information on the [Drupal.org issue](https://www.drupal.org/project/letsencrypt_challenge/issues/2918028)
We set up the LetsEncrypt Challenge module on the site. This allows us to manually set the challenge string.
Work has been done in the module to also read from a file, which acme.sh will create automatically during issue and renewal.
To set the directory for that file, we add the path in the settings.php, or in our case the `common.settings.php` file.
`$settings['letsencrypt_challenge_directory'] = "/mnt/gfs/{$_ENV['AH_SITE_GROUP']}.{$_ENV['AH_SITE_ENVIRONMENT']}/files/";`

This tells the LetsEncrypt Challenge to read from the `letsencrypt_challenge_directory` directory. Inside that directory 
acme.sh will create challenge files in `.well-known/acme-challenge/`. The module will read each of those files as a
individual page found at `http://domain.com/.well-known/acme-challenge/[filename]`. It will read the contents of that file
and return that to the output on the page.

Now that we have a way to confirm the challenge we can begin to issue certificates.

## Issue Certs
Simply running the following command will begin issuing the LetsEncrypt Certificate. This command should be run on Acquia servers.
`~/.acme.sh/acme.sh --issue -d example.com -d www.example.com -d cp.example.com -w /mnt/gfs/[stitename].[environment]/files/`
Replace the sitename and environment tokens with appropriate strings.

If all is set up correctly, the command should register the certificate for each domain after completing the challenge.
The certificate files are now on Acquia server in the directory `~/.acme.sh/[example.com]` (whichever domain was listed first during issue)

## Add Certs to Acquia dashboard
In the Acquia dashboard for the appropriate application, click on the appropriate environment where the certificate matches the domains on the environment
On the right sidebar, click "SSL" and then click to "Install SSL Certificate"
The label is anything you desire and the rest of the contents are on Acquia server:
SSL Certificate: `~/.acme.sh/[example.com]/[example.com].cer`
SSL private key: `~/.acme.sh/[example.com]/[example.com].key`
CA intermediate certificates: `~/.acme.sh/[example.com]/ca.cer`

After adding the certificate and activating, the result should be visible within a couple minutes.

## AutoRenew Certs
Still in the Acquia dashboard in the appropriate environment, in the right sidebar click "Scheduled Jobs" and at the top click "Add Job"
All that is needed here is to call the acme.sh script `/home/[sitename]/.acme.sh/acme.sh --renew-all`. Set this to execute
once each week and you now how cert renewal.
If desired for logging you can add ` &>> /home/[sitename]/.acme.sh/logs/cert_renewal.log` to the end of the command and log all renewal attempts.

## Helpers in this setup
In our installation we have a few helpful commands added to BLT. These help in adding domains to certs and getting cert contents.

### List Certs
`blt humsci:letsencrypt:list` will give you a list of all certs on the environment. It also gives a list of all the domains
on the dev cert. Each environment has its own cert. So we can run `blt humsci:letsencrypt:list prod` and get the list of
domains on the production certificate.

### Add Domain To Cert
`blt humsci:letsencrypt:add-domain [environment]` will begin the task of adding a new domain to an environment certificate.
It will prompt for the new domains. Simply leave a line empty to end adding additional domains. This command requires the domain
to already be added to the Acquia environment and have the LetsEncrypt Challenge module enabled.

### Get Cert Contents
`blt humsci:letsencrypt:get-cert [environment]` will prompt you for which certificate file you wish to view. It will
display the contents of that file for quick copy and paste into Acquia dashboard.
