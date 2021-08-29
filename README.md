# friendica-marketplace
Simple marketplace plugin for friendica with bitcoin wallet support
---------------------------
Update your device
---------------------------

First, get a fresh installation of debian or an offshoot like ubuntu or raspian. You can do this on a new computer, e.g. a raspberry pi, or by following the steps in my video, “How to Setup a Remote Desktop in Bitclouds.”

Log into your linux device via ssh.

ssh username@ipaddress

If you logged in as root, install sudo if you do not already have it.

apt install sudo -y

Update your device.

sudo apt update -y && sudo apt upgrade -y && sudo apt autoremove -y

If it asks you to confirm anything hit y and then enter

---------------------------
Install and configure lnd
---------------------------

sudo apt install wget tar tmux -y

Go to this page: https://github.com/lightningnetwork/lnd/releases/tag/v0.13.0-beta or whatever the latest version of LND is

Scroll to the bottom and select an option that matches your chipset. Most people have linux amd64 but if you’re on a pi go with one of the linux arm options, not sure which to be honest. Right click it and copy the address.

Then run this:

wget https://github.com/lightningnetwork/lnd/releases/download/v0.13.0-beta/lnd-linux-amd64-v0.13.0-beta.tar.gz

Except replace the link with whatever version of linux you copied in the previous step. Then:

tar -xvf lnd-linux-amd64-v0.13.0-beta.tar.gz
rm lnd-linux-amd64-v0.13.0-beta.tar.gz

Except replace the filename with whatever file you downloaded.

mv * lnd
sudo mv lnd/* /usr/local/bin/
rm -rf lnd

Yay, LND is installed! Now let’s configure it.

cd
mkdir .lnd
nano .lnd/lnd.conf

Enter the following lines of text:

[Application Options]
debuglevel=info
maxpendingchannels=5
rpclisten=localhost:10009
feeurl=https://nodes.lightning.computer/fees/v1/btc-fee-estimates.json

[Bitcoin]
bitcoin.active=1
bitcoin.mainnet=1
bitcoin.node=neutrino

Type ctrl+x to exit. Near the bottom of the editor there should be a prompt asking you if you want to “save [the] modified buffer.” Type the letter y and hit enter.

Use tmux to start lnd

tmux new-session
lnd

You should see a bunch of text start scrolling by while lnd fires up. That’s good. Exit tmux and leave lnd running in the background.

Enter ctrl+b and then type the letter d to exit tmux while leaving lnd running in the background

Rename the tmux session lnd

tmux rename-session -t 0 lnd

Create a lightning wallet

lncli create

Enter a new password for your wallet and confirm it. Enter n when it asks if you have an existing seed. Enter a second password if you want one, otherwise hit enter. Copy your seed phrase somewhere safe, it will control some money!

Congratulations, lnd is up and running!

------------------------------
Install lnbits
------------------------------

sudo apt purge python3 -y
sudo apt-mark manual sudo
sudo apt autoremove -y
sudo apt install make build-essential libssl-dev zlib1g-dev libbz2-dev libreadline-dev libsqlite3-dev wget curl llvm libncursesw5-dev xz-utils tk-dev libxml2-dev libxmlsec1-dev libffi-dev liblzma-dev git -y
curl https://pyenv.run | bash

If your username is root:

PATH="/root/.pyenv/bin:"$PATH

If your username is not root:

PATH="/home/[insert your linux username here---no brackets]/.pyenv/bin:"$PATH

Then:

cd
git clone https://github.com/lnbits/lnbits.git
cd lnbits/
pyenv install 3.8.3
~/.pyenv/versions/3.8.3/bin/python3 -m venv venv
./venv/bin/pip install -r requirements.txt
./venv/bin/pip install lndgrpc
./venv/bin/pip install purerpc
cp .env.example .env

Use the nano editor to modify your .env file:

nano .env

Change these lines:

LNBITS_BACKEND_WALLET_CLASS=LndWallet
LND_GRPC_PORT=10009
LND_GRPC_CERT='/home/[insert your linux username here---no brackets]/.lnd/tls.cert'
LND_GRPC_MACAROON='/home/[insert your linux username here---no brackets]/.lnd/data/chain/bitcoin/mainnet/admin.macaroon'

Type ctrl+x to exit. Near the bottom of the editor there should be a prompt asking you if you want to “save [the] modified buffer.” Type the letter y and hit enter.

Then:

mkdir data
./venv/bin/quart assets
./venv/bin/quart migrate

Use tmux to start lnbits

tmux new-session
sudo ./venv/bin/hypercorn -k trio --bind 0.0.0.0:5000 'lnbits.app:create_app()'

You should see a bunch of text start scrolling by while lnd fires up. That’s good. Exit tmux and leave lnd running in the background.

Enter ctrl+b and then type the letter d to exit tmux while leaving lnd running in the background

Rename the tmux session lnd

tmux rename-session -t 1 lnbits

Congratulations, lnbits is up and running!

---------------------------
Install friendica
---------------------------

If you are on ubuntu 20 or debian 10, skip the next indented paragraph. If you are on ubuntu 18.04 or older, or debian 9 or older, don’t skip them.

Lay the groundwork for installing a version of php that is higher than 6.9 which is a dependency for friendica.

cd
wget -q https://packages.sury.org/php/apt.gpg -O- | sudo apt-key add -
echo "deb https://packages.sury.org/php/ stretch main" | sudo tee /etc/apt/sources.list.d/php.list
sudo apt update -y && sudo apt upgrade -y && sudo apt autoremove -y

Install php (the language friendica is written in), the php-fpm plugin (which lets php run in the background), and the php-mysql plugin (which lets php work with some database software that we will install later).

sudo apt install php php-fpm libapache2-mod-php php-common php-gmp php-curl php-intl php-mbstring php-xmlrpc php-mysql php-gd php-imagick php-xml php-cli php-zip php-sqlite3 curl git zip -y

On ubuntu and debian 9, installing php automatically also installs, configures, and starts apache2. We need to stop apache2 and replace it with nginx.

sudo systemctl stop apache2 && sudo systemctl disable apache2
sudo apt install nginx -y
sudo rm /var/www/html/*
sudo systemctl enable nginx && sudo systemctl start nginx

In order to run friendica, we first need database software because friendica is heavily reliant on databases. I recommend mariadb because it is free, open source, and based on the popular mysql program.

sudo apt install mariadb-server mariadb-client -y

Run mariadb.

sudo mariadb

Your terminal will change slightly when you run mariadb.

CREATE DATABASE friendica;

On the next line, replace the number 12345 with an actual, secure password.

CREATE USER 'admin'@'localhost' IDENTIFIED BY '12345';

You can enter the following commands all at once:

GRANT ALL ON friendica.* TO 'admin'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
exit;

Great! Now your database is installed and configured. Download friendica using these commands:

cd
wget https://github.com/friendica/friendica/archive/refs/tags/2021.07.tar.gz # you may need to change that url, see below

Check this page for the latest download url: https://github.com/friendica/friendica/releases -- the file should look something like this: Source code (tar.gz) and its url should look something like this: https://github.com/friendica/friendica/archive/refs/tags/2021.07.tar.gz ← the date “2021.07” may be different for you depending on when you are following these instructions. Extract the tar file and remove the tar file now that you’ve extracted its contents.

tar -xvf 2021.07.tar.gz # change that filename to match yours if necessary
rm 2021.07.tar.gz # change that filename to match yours if necessary

Change directories into the resulting folder.

cd friendica-full-2021.07 # the date “2021.07” may be different for you

Download the addons package.

wget https://github.com/friendica/friendica-addons/archive/refs/tags/2020.09.zip # you may need to change that url, see below

Check this page for the latest download url: https://github.com/friendica/friendica-addons/releases -- the file should look something like this: Source code (zip) and its url should look something like this: https://github.com/friendica/friendica-addons/archive/refs/tags/2020.09.zip ← the date “2020.09” may be different for you depending on when you are following these instructions. Extract the zip file, remove the zip file now that you’ve extracted its contents, and change the name of the resulting directory to addon.

unzip 2020.09.zip # change that filename to match yours if necessary
rm 2020.09.zip # change that filename to match yours if necessary
mv friendica-addons-2020.09 addon # change the date to match the one in the name of your friendica-addons directory if necessary

Move the friendica folder -- including all addons -- to /var/www/html/ and rename the folder “friendica.”

cd
sudo mv friendica-full-2021.07/ /var/www/html/friendica/

Change directories into the new friendica folder and run composer.

cd /var/www/html/friendica/
sudo apt install composer -y
composer install --no-dev

Change ownership and mode of access for all files in friendica to www-data and 775.

sudo chown -R www-data:www-data /var/www/html/friendica/
sudo chmod -R 755 /var/www/html/friendica/

-----------------------------
Configure nginx for both friendica and lnbits
-----------------------------

Use the nano editor to edit nginx’s config file

sudo rm /etc/nginx/sites-enabled/default
sudo nano /etc/nginx/sites-enabled/default

Copy paste the lines mentioned two indentations below, but you may need to modify this line: fastcgi_pass unix:/run/php/php7.2-fpm.sock;

That is because you may not have php7.2. Find out what version of php you have this way:

whereis php7

If that doesn’t turn up anything you may have php8 or php9 so try whereis php8 or php9. The version number you see as a result might be 7.2, 7.4, 8.0, or something else. If your number is not 7.2, replace the number 7.2 in the line mentioned with 7.4 or 8.0 or whatever your number is instead. You will also have to change example.com to your donation name in both places where it appears.

server {
        listen 80 default_server;
        listen [::]:80 default_server;

        root /var/www/html/friendica;
        index index.php index.html index.htm index.nginx-debian.html;

        # in my case example.com is a8088f8423b9.ngrok.io
        server_name example.com;

        location / {
                try_files $uri $uri/ /index.php?q=$uri&$args;
        }

        location ~\.php$ {
                include snippets/fastcgi-php.conf;
                fastcgi_pass unix:/run/php/php7.2-fpm.sock;
        }
}

server {
    listen 80;
    server_name lnbits.example.com;

    location / {
        proxy_pass http://localhost:5000;
    }   
}

Type ctrl+x to exit. Near the bottom of the editor there should be a prompt asking you if you want to “save [the] modified buffer.” Type the letter y and hit enter.

Restart nginx

sudo service nginx restart

------------------------------
Go configure your dns to point both example.com and lnbits.example.com to your lnbits instance
------------------------------

It’s hard to give instructions for this part because the procedure depends on your domain name service. You need to set some “a” records for your domains so that they point to your ip address. If your domain is example.com, you’ll need both example.com and lnbits.example.com to point to the ip address where you are hosting your friendica instance.

------------------------------
Get ssl certificates
------------------------------

Run these commands

sudo apt install python3-certbot-nginx -y
sudo certbot --nginx -d example.com -d www.example.com -d lnbits.example.com

Follow the prompts. When it asks you about setting up redirects so that everything goes over https, select yet by typing the number 2 and then hitting enter.

------------------------------
Configure php to work with friendica
------------------------------

In the following command, replace 7.2 with whatever your php version number is based on what you found out earlier when configuring nginx. Use the nano editor to edit php-fpm.

sudo nano /etc/php/7.2/fpm/php.ini

Change the following lines. If any of them are preceded by semicolons (except “short_open_tag,” see below), remove the semicolons.

file_uploads = On
allow_url_fopen = On
short_open_tag = On

Note that for “short_open_tag,” if you do not see an easy way to change it but you do see “Default Value: On,” that’s fine, keep going.

memory_limit = 256M
cgi.fix_pathinfo = 0
upload_max_filesize = 100M
max_execution_time = 360
max_input_vars = 1500
date.timezone = America/Chicago

Type ctrl+x to exit. Near the bottom of the editor there should be a prompt asking you if you want to “save [the] modified buffer.” Type the letter y and hit enter. Reload nginx

sudo service nginx restart

Use the nano editor to modify your hosts file to forward all traffic coming from your own server to the local host (otherwise it gets stuck in a loop)

sudo nano /etc/hosts

Enter this text on the second and third lines:

127.0.0.1       example.com
127.0.0.1       lnbits.example.com

Congratulations, friendica is up and running!

------------------------------
Set up friendica’s frontend
------------------------------

Visit your website. If you see an error about .htaccess files and rewrites, you can ignore that, that is only there because nginx does not use .htaccess files and consequently it rewrites things differently. The job it needs to do will still get done just fine.

Click Next
Click Submit

Enter this information into the fields.

Database server name
localhost

Database login name
admin

Database login password
Whatever you set up earlier

Database name
friendica

Click Submit. Enter an email and ensure the time zone matches what you entered earlier when configuring php-fpm. (In my example, America/Chicago.) Click Submit.

Visit your domain with a slash and register (e.g. example.com/register) and create an account. 

------------------------------
Install the marketplace plugin
------------------------------

Download the plugin.

git clone https://github.com/supertestnet/friendica-marketplace.git

Visit your site and use the menu bar to go to the Admin section. Click Addons in the lefthand sidebar. Activate Marketplace App.

------------------------------
Configure the marketplace plugin
------------------------------

Visit your lnbits domain name and create a wallet. I named mine “Admin wallet” but you can name yours whatever you want. Click Manage extensions. Activate User Manager. Click your wallet’s name (e.g. Admin wallet) in the lefthand sidebar. Click API Info in the righthand sidebar. Note your admin key and note the “usr” parameter in the url.

![lnbits](https://github.com/simong1080/friendica-marketplace/blob/main/lnbits.png)

You will use those values in a few moments.

Visit your friendica site. Use the menu bar to go to the Marketplace Admin section. In the “Lnbits domain” field, enter your lnbits domain name, e.g. https://lnbits.example.com. In the “Lnbits admin user” field, enter the usr string from the url, e.g. a5b104044c614af4968cdf8d15d557f3. In the “Lnbits admin key” field, enter the admin key string from the API Info sidebar, e.g. 69e06687ab174b298fbeba88324ff569. Hit submit.

Congratulations, the plugin is set up! Now you can create products and pay people for their goods.
