#!/bin/bash
# This script will install a new MEGA World database with a random password.
# If /root/.my.cnf exists then it won't ask for root password
  echo "Creating a fresh MEGA World database."
  # echo "This assumes that the root user has the ability to create new databases and users."
  echo
  echo "You will be asked to enter the database root user password."
  echo "WARNING: If the specified database already exists it will be completely deleted!"
  echo
  echo "Database name [ mega ]:"
	read dbname
	dbname=${dbname:-mega}
	echo
	echo "Database username [ mega ]:"
	read dbuser
	dbuser=${dbuser:-mega}
	echo
	echo "You may use this random password, but it is recommended that you set a good password yourself."
	randomPassword="$(openssl rand -base64 16)"
	echo "Password for ${dbuser} [ ${randomPassword} ]"
	read -s password
	password=${password:-$randomPassword}

  echo "Enter the MySQL root password to create the '${dbname}' database and '${dbuser} user (you won't see what you type):"
	mysql -uroot -p -e "CREATE DATABASE IF NOT EXISTS ${dbname} CHARACTER SET utf8mb4; CREATE USER IF NOT EXISTS ${dbuser}@localhost IDENTIFIED BY '${password}';GRANT ALL PRIVILEGES ON ${dbname}.* TO '${dbuser}'@'localhost';FLUSH PRIVILEGES;"
	echo "Database and user successfully created."

	# echo "Enter the new '${dbuser}' password that you just set to install the MEGA World database:"
	mysql -u ${dbuser} --password="$password" ${dbname} < mega_database_mysql.sql
	echo "MEGA World database successfully created!"

  echo
  echo "Enter a STRONG password for the default management admin user:"
  read -s mgmtpassword
  mysql -u ${dbuser} --password="$password" ${dbname} -e "INSERT INTO management_users (name, password_old_sha1, time_zone, created_by, created_at) VALUES('admin', SHA('${mgmtpassword}'), 'UTC', 'install_mega_db', NOW());"

	echo "Management user 'admin' created with your chosen password."
	exit
