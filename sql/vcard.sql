-- VCARD Schema based on RFC6350
-- Targeting VCard 4.0 spec
-- http://tools.ietf.org/html/rfc6350
-- @author Eric Vought (evought@pobox.com) 2014-11-16
-- based on work by George_h on Stackoverflow for RFC6868/VCard 3.0
-- @copyright Eric Vought 2014, Some rights reserved.
-- @license CC-BY 4.0 http://creativecommons.org/licenses/by/4.0/

-- create database VCARD;

create table CONTACT_ADR
(
    ADR_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    POBOX VARCHAR(30), -- Deprecated
    EXTENDED_ADDRESS VARCHAR(255), -- Deprecated
    STREET VARCHAR(255) NOT NULL,
    LOCALITY VARCHAR(50),
    REGION VARCHAR(50),
    POSTAL_CODE VARCHAR(30),
    COUNTRY VARCHAR(50),
    PRIMARY KEY(ADR_ID)
);

create table CONTACT_PHONE_NUMBER
(
    PHONE_NUMBER_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    LOCAL_NUMBER VARCHAR(255) NOT NULL,         -- Free form telephone number
    PRIMARY KEY(PHONE_NUMBER_ID)
);

create table CONTACT_EMAIL
(
    EMAIL_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    EMAIL_ADDRESS VARCHAR(255) NOT NULL,
    PRIMARY KEY(EMAIL_ID)
);

create table CONTACT_AGENT
(
    AGENT_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    URI VARCHAR(255) NOT NULL,
    PRIMARY KEY(AGENT_ID)
);

create table CONTACT_CATEGORIES
(
    CATEGORY_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    CATEGORY_NAME VARCHAR(255) NOT NULL,
    PRIMARY KEY(CATEGORY_ID)
);

create table CONTACT_NOTE
(
    NOTE_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    NOTE TEXT NOT NULL,
    PRIMARY KEY(NOTE_ID)
);

create table CONTACT_XTENDED
(
    XTENDED_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    XNAME VARCHAR(255) NOT NULL,
    XVALUE VARCHAR(255) NOT NULL,
    PRIMARY KEY(XTENDED_ID)
);

create table CONTACT_KEYS
(
    KEY_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    KEY_DATA TEXT NOT NULL,
    PRIMARY KEY(KEY_ID)
);

create table CONTACT_DATA
(
    CONTACT_DATA_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    DATA_NAME VARCHAR(10) NOT NULL,             -- [LOGO,PHOTO,SOUND]
    URL VARCHAR(255),
    INLINE CHAR(1),
    DATA MEDIUMBLOB,
    PRIMARY KEY(CONTACT_DATA_ID)
);

-- Implements the ORG field from 6.6.4 of rfc 6350
-- The subunits, UNIT1 and UNIT2 could be broken out into a link table
-- to support more than two, but in practice it isn't used much, and
-- the link would need to preserve order, so probably not worth complexity.
-- By doing it this way, we also match behavior of php.[EMV]
create table CONTACT_ORG
(
    ORG_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    NAME VARCHAR(255) NOT NULL,
    UNIT1 VARCHAR(255),
    UNIT2 VARCHAR(255),
    PRIMARY KEY(ORG_ID)
);

create table CONTACT_ENCODING_TYPES
(
    ENCODING_TYPE_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    TYPE_NAME VARCHAR(20) NOT NULL,
    PRIMARY KEY(ENCODING_TYPE_ID)
);

-- N (name) is a structured field in the spec. We divide it into its
-- subfields here (N_GIVEN_NAME, N_ADDIT_NAME, etc). FN is the formatted full
-- name.
-- NAME, MAILER, LABEL, and CLASS were removed in RFC6350.
-- We do not store PROD_ID because we will set that on export.
create table CONTACT
(
    CONTACT_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    KIND VARCHAR(20),
    FN VARCHAR(255) NOT NULL,
    N_GIVEN_NAME VARCHAR(50),
    N_ADDIT_NAME VARCHAR(50),
    N_FAMILY_NAME VARCHAR(50),
    N_PREFIX VARCHAR(50),
    N_SUFFIX VARCHAR(50),
    NICKNAME VARCHAR(255),
    BDAY TIMESTAMP NULL,
    TZ CHAR(3),                 -- Time zone offset in hours
    GEO_LAT DOUBLE,             -- Latitude
    GEO_LONG DOUBLE,            -- Longitude
    TITLE VARCHAR(50),
    ROLE VARCHAR(50),
    REV VARCHAR(50),
    SORT_STRING VARCHAR(50),
    UID VARCHAR(255),
    URL VARCHAR(255),
    VERSION VARCHAR(10),
    PRIMARY KEY(CONTACT_ID)
);

create table CONTACT_REL_ADR
(
    CONTACT_ID MEDIUMINT NOT NULL,
    ADR_ID MEDIUMINT NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID),
    FOREIGN KEY(ADR_ID) REFERENCES CONTACT_ADR(ADR_ID),
    PRIMARY KEY(CONTACT_ID,ADR_ID)
);

create table CONTACT_REL_PHONE_NUMBER
(
    CONTACT_ID MEDIUMINT NOT NULL,
    PHONE_NUMBER_ID MEDIUMINT NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID),
    FOREIGN KEY(PHONE_NUMBER_ID) REFERENCES CONTACT_PHONE_NUMBER(PHONE_NUMBER_ID),
    PRIMARY KEY(CONTACT_ID,PHONE_NUMBER_ID)
);

create table CONTACT_REL_EMAIL
(
    CONTACT_ID MEDIUMINT NOT NULL,
    EMAIL_ID MEDIUMINT NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID),
    FOREIGN KEY(EMAIL_ID) REFERENCES CONTACT_EMAIL(EMAIL_ID),
    PRIMARY KEY(CONTACT_ID,EMAIL_ID)
);

create table CONTACT_REL_CATEGORIES
(
    CONTACT_ID MEDIUMINT NOT NULL,
    CATEGORY_ID MEDIUMINT NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID),
    FOREIGN KEY(CATEGORY_ID) REFERENCES CONTACT_CATEGORIES(CATEGORY_ID),
    PRIMARY KEY(CONTACT_ID,CATEGORY_ID)
);

create table CONTACT_REL_NOTE
(
    CONTACT_ID MEDIUMINT NOT NULL,
    NOTE_ID MEDIUMINT NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID),
    FOREIGN KEY(NOTE_ID) REFERENCES CONTACT_NOTE(NOTE_ID),
    PRIMARY KEY(CONTACT_ID,NOTE_ID)
);

create table CONTACT_REL_DATA
(
    CONTACT_ID MEDIUMINT NOT NULL,
    CONTACT_DATA_ID MEDIUMINT NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID),
    FOREIGN KEY(CONTACT_DATA_ID) REFERENCES CONTACT_DATA(CONTACT_DATA_ID),
    PRIMARY KEY(CONTACT_ID,CONTACT_DATA_ID)
);

create table CONTACT_REL_AGENT
(
    CONTACT_ID MEDIUMINT NOT NULL,
    AGENT_ID MEDIUMINT NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID),
    FOREIGN KEY(AGENT_ID) REFERENCES CONTACT_AGENT(AGENT_ID),
    PRIMARY KEY(CONTACT_ID,AGENT_ID)
);

create table CONTACT_REL_KEYS
(
    CONTACT_ID MEDIUMINT NOT NULL,
    KEY_ID MEDIUMINT NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID),
    FOREIGN KEY(KEY_ID) REFERENCES CONTACT_KEYS(KEY_ID),
    PRIMARY KEY(CONTACT_ID,KEY_ID)
);

create table CONTACT_REL_XTENDED
(
    CONTACT_ID MEDIUMINT NOT NULL,
    XTENDED_ID MEDIUMINT NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID),
    FOREIGN KEY(XTENDED_ID) REFERENCES CONTACT_XTENDED(XTENDED_ID),
    PRIMARY KEY(CONTACT_ID,XTENDED_ID)
);

create table CONTACT_REL_ORG
(
    CONTACT_ID MEDIUMINT NOT NULL,
    ORG_ID MEDIUMINT NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID),
    FOREIGN KEY(ORG_ID) REFERENCES CONTACT_ORG(ORG_ID),
    PRIMARY KEY(CONTACT_ID, ORG_ID)
);

-- A series of tables to join types to properties as described in RFC6350
-- Sec 5.6. Each of those properties, such as an address, can have zero or
-- more TYPES and TYPES behave a bit differently for, say, TEL and ADDRESS,
-- so must be implemented as link tables.

--
create table CONTACT_ADR_REL_TYPES
(
    ADR_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(ADR_ID) REFERENCES CONTACT_ADR(ADR_ID),
    PRIMARY KEY(ADR_ID,TYPE_NAME)
);

-- RFC6350 6.4.1 defines TYPES available for TEL in addition to work, home.
create table CONTACT_PHONE_NUMBER_REL_TYPES
(
    PHONE_NUMBER_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(PHONE_NUMBER_ID) REFERENCES CONTACT_PHONE_NUMBER(PHONE_NUMBER_ID),
    PRIMARY KEY(PHONE_NUMBER_ID,TYPE_NAME)
);

create table CONTACT_EMAIL_REL_TYPES
(
    EMAIL_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(EMAIL_ID) REFERENCES CONTACT_EMAIL(EMAIL_ID),
    PRIMARY KEY(EMAIL_ID,TYPE_NAME)
);

create table CONTACT_NOTE_REL_TYPES
(
    NOTE_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(NOTE_ID) REFERENCES CONTACT_NOTE(NOTE_ID),
    PRIMARY KEY(NOTE_ID,TYPE_NAME)
);

create table CONTACT_KEY_REL_TYPES
(
    KEY_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(KEY_ID) REFERENCES CONTACT_KEYS(KEY_ID),
    PRIMARY KEY(KEY_ID,TYPE_NAME)
);

create table CONTACT_DATA_REL_TYPES
(
    CONTACT_DATA_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(CONTACT_DATA_ID) REFERENCES CONTACT_DATA(CONTACT_DATA_ID),
    PRIMARY KEY(CONTACT_DATA_ID,TYPE_NAME)
);

create table CONTACT_ORG_REL_TYPES
(
    ORG_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(ORG_ID) REFERENCES CONTACT_ORG(ORG_ID),
    PRIMARY KEY(ORG_ID,TYPE_NAME)
);

create table CONTACT_DATA_REL_ENCODING_TYPES
(
    CONTACT_DATA_ID MEDIUMINT NOT NULL,
    ENCODING_TYPE_ID MEDIUMINT NOT NULL,
    FOREIGN KEY(CONTACT_DATA_ID) REFERENCES CONTACT_DATA(CONTACT_DATA_ID),
    FOREIGN KEY(ENCODING_TYPE_ID) REFERENCES CONTACT_ENCODING_TYPES(ENCODING_TYPE_ID),
    PRIMARY KEY(CONTACT_DATA_ID,ENCODING_TYPE_ID)
);


