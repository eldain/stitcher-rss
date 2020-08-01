#Assumes you're logged in as root

#install sudo and vim (vim is my personal preference of editor, you can use whatever you prefer)
apt install -y sudo vim

#create the user to perform the install as - I named mine "stitcher"
useradd -m stitcher

#create a password for the new user so you can login to it and for sudo commands
passwd stitcher

#add stitcher to the sudo group
usermod -aG sudo stitcher

#Switch to the stitcher user
su - stitcher

#update app repositories
sudo apt update

#install a bunch of prereqs
sudo apt install php7.3 php7.3-xml php7.3-mysql php7.3-zip php7.3-redis composer nodejs gnupg2 lsb-release redis nginx php7.3-fpm mariadb-server git curl

#secure the mariadb installation - choose "y" for every question
sudo mysql_secure_installation

#create a database for stitcher
sudo mariadb

MariaDB [(none)]> create database stitcher;
Query OK, 1 row affected (0.000 sec)

MariaDB [(none)]> grant all on stitcher.\* to 'stitcher'@'localhost' identified by 'stitcher' with grant option;
Query OK, 0 rows affected (0.000 sec)

MariaDB [(none)]> flush privileges;
Query OK, 0 rows affected (0.000 sec)

MariaDB [(none)]> exit

#create a root web directory for stitcher
sudo mkdir /var/www/stitcher

#enter the new directory above
cd /var/www/stitcher

#clone the stitcher feed code
git clone https://gitlab.com/adduc-projects/stitcher-rss-2.git

#enter the new cloned directory
cd /var/www/stitcher/stitcher-rss-2

#copy the .env file from the template
cp .env.example .env

#edit the .env file with the appropriate values. In my example, the only things you need to update are the DB name, the DB user and DB password
vim .env

DB_DATABASE=stitcher
DB_USERNAME=stitcher
DB_PASSWORD=stitcher

#type this to save and quit vim: :wq

#we need to install yarn from an external repo
#I followed this guide: https://linuxize.com/post/how-to-install-yarn-on-debian-10/
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
sudo apt update
sudo apt install yarn

#make sure we're in the root directory of the stitcher app
cd /var/www/stitcher/stitcher-rss-2

#run compose stuff
composer install

#run more compose stuff
composer webpack

#initialize the database
./artisan migrate

#change permissions
cd /var/www
sudo chown -R www-data:www-data stitcher/

#give full access to storage directory
sudo chmod -R 777 /var/www/stitcher/stitcher-rss-2/storage

#setup nginx
#create a new config for this site
vim /etc/nginx/sites-available/stitcher

#inside this file:

server {
listen 8080;
listen [::]:8080;

    root /var/www/stitcher/stitcher-rss-2/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
    }

}

#save and quit (:wq)

#link the available config to enabled
sudo ln -s /etc/nginx/sites-available/stitcher /etc/nginx/sites-enabled/stitcher

#note, the above config will be accessible on port 8080 rather than the default port 80. You can change this to whatever port you'd like.

#restart nginx and cross your fingers that your site works:
sudo systemctl restart nginx

#Now you should be able to see if it works by going to http://<<your ip>>:8080

#NOTE: I could not get this to work when using a local IP. I had to do some port forwarding on my router and visit the site by http://<<my external internet IP address>>:<<forwarded port>> to get the feeds working in Pocket Casts
