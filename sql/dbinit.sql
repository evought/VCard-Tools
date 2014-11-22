-- SQL for creating the VCARD database
-- Need to run as mysql root or mysql admin
-- @author Eric Vought (evought@pobox.com) 2014-11-16 work-for-hire

create user 'sortafrica'@'localhost' identified by 'testuser';


create database VCARD;

grant select, insert, update, delete on VCARD.* to 'sortafrica'@'localhost';
