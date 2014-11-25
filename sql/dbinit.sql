-- SQL for creating the VCARD database
-- Need to run as mysql root or mysql admin
-- @author Eric Vought (evought@pobox.com) 2014-11-16

create user 'vcard-test'@'localhost' identified by 'wad%51lumpkin';


create database VCARD;

grant select, insert, update, delete on VCARD.* to 'vcard-test'@'localhost';
