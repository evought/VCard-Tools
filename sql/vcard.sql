-- VCARD Schema based on RFC6350
-- Targeting VCard 4.0 spec
-- http://tools.ietf.org/html/rfc6350
-- @author Eric Vought (evought@pobox.com) 2014-11-16
-- based on work by George_h on Stackoverflow for RFC6868/VCard 3.0
-- @copyright Eric Vought 2014, Some rights reserved.
-- @license CC-BY 4.0 http://creativecommons.org/licenses/by/4.0/

-- create database VCARD;

-- FN is the formatted full
-- name.
-- NAME, MAILER, LABEL, and CLASS were removed in RFC6350.
-- We do not store PROD_ID because we will set that on export.
create table CONTACT
(
    CONTACT_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    KIND VARCHAR(20),
    FN VARCHAR(255) NOT NULL,
    NICKNAME VARCHAR(255),
    BDAY TIMESTAMP NULL,
    ANNIVERSARY TIMESTAMP NULL,
    TZ CHAR(3),                 -- Time zone offset in hours
    TITLE VARCHAR(50),
    ROLE VARCHAR(50),
    REV VARCHAR(50),
    SORT_STRING VARCHAR(50),
    UID VARCHAR(255),
    URL VARCHAR(255),
    VERSION VARCHAR(10),
    PRIMARY KEY(CONTACT_ID)
);

-- Contains a RFC5870 GEO URI
-- @see https://tools.ietf.org/html/rfc5870
-- @see https://tools.ietf.org/html/rfc6350#section-6.5.2
create table CONTACT_GEO
(
    GEO_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    CONTACT_ID MEDIUMINT NOT NULL,
    GEO VARCHAR(255) NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(GEO_ID)
);

create table CONTACT_N
(
    N_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    CONTACT_ID MEDIUMINT NOT NULL,
    GIVEN_NAME VARCHAR(50),
    ADDIT_NAME VARCHAR(50),
    FAMILY_NAME VARCHAR(50),
    PREFIXES VARCHAR(50),
    SUFFIXES VARCHAR(50),
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(N_ID)
);

create table CONTACT_ADR
(
    ADR_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    CONTACT_ID MEDIUMINT NOT NULL,
    POBOX VARCHAR(30), -- Deprecated
    EXTENDED_ADDRESS VARCHAR(255), -- Deprecated
    STREET VARCHAR(255) NOT NULL,
    LOCALITY VARCHAR(50),
    REGION VARCHAR(50),
    POSTAL_CODE VARCHAR(30),
    COUNTRY VARCHAR(50),
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(ADR_ID)
);

create table CONTACT_TEL
(
    TEL_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    CONTACT_ID MEDIUMINT NOT NULL,
    TEL VARCHAR(255) NOT NULL, -- free-form telephone number
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(TEL_ID)
);

create table CONTACT_EMAIL
(
    EMAIL_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    CONTACT_ID MEDIUMINT NOT NULL,
    EMAIL VARCHAR(255) NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(EMAIL_ID)
);

create table CONTACT_AGENT
(
    AGENT_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    CONTACT_ID MEDIUMINT NOT NULL,
    URI VARCHAR(255) NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(AGENT_ID)
);

create table CONTACT_CATEGORIES
(
    CATEGORY_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    CONTACT_ID MEDIUMINT NOT NULL,
    CATEGORY VARCHAR(255) NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(CATEGORY_ID)
);

create table CONTACT_NOTE
(
    NOTE_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    CONTACT_ID MEDIUMINT NOT NULL,
    NOTE TEXT NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(NOTE_ID)
);

create table CONTACT_XTENDED
(
    XTENDED_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    CONTACT_ID MEDIUMINT NOT NULL,
    XNAME VARCHAR(255) NOT NULL,
    XVALUE VARCHAR(255) NOT NULL,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(XTENDED_ID)
);

create table CONTACT_DATA
(
    CONTACT_DATA_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    CONTACT_ID MEDIUMINT NOT NULL,
    DATA_NAME VARCHAR(10) NOT NULL,             -- [LOGO,PHOTO,SOUND,KEY]
    URL VARCHAR(255),
    INLINE CHAR(1),
    DATA MEDIUMBLOB,
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
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
    CONTACT_ID MEDIUMINT NOT NULL,
    NAME VARCHAR(255) NOT NULL,
    UNIT1 VARCHAR(255),
    UNIT2 VARCHAR(255),
    FOREIGN KEY(CONTACT_ID) REFERENCES CONTACT(CONTACT_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(ORG_ID)
);

create table CONTACT_ENCODING_TYPES
(
    ENCODING_TYPE_ID MEDIUMINT NOT NULL AUTO_INCREMENT,
    TYPE_NAME VARCHAR(20) NOT NULL,
    PRIMARY KEY(ENCODING_TYPE_ID)
);

-- A series of tables to join types to properties as described in RFC6350
-- Sec 5.6. Each of those properties, such as an address, can have zero or
-- more TYPES and TYPES behave a bit differently for, say, TEL and ADDRESS,
-- so must be implemented as link tables.

--
create table CONTACT_GEO_REL_TYPES
(
    GEO_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(GEO_ID) REFERENCES CONTACT_GEO(GEO_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(GEO_ID, TYPE_NAME)
);

create table CONTACT_ADR_REL_TYPES
(
    ADR_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(ADR_ID) REFERENCES CONTACT_ADR(ADR_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(ADR_ID,TYPE_NAME)
);

-- RFC6350 6.4.1 defines TYPES available for TEL in addition to work, home.
create table CONTACT_TEL_REL_TYPES
(
    TEL_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(TEL_ID) REFERENCES CONTACT_TEL(TEL_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(TEL_ID,TYPE_NAME)
);

create table CONTACT_EMAIL_REL_TYPES
(
    EMAIL_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(EMAIL_ID) REFERENCES CONTACT_EMAIL(EMAIL_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(EMAIL_ID,TYPE_NAME)
);

create table CONTACT_NOTE_REL_TYPES
(
    NOTE_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(NOTE_ID) REFERENCES CONTACT_NOTE(NOTE_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(NOTE_ID,TYPE_NAME)
);

create table CONTACT_DATA_REL_TYPES
(
    CONTACT_DATA_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(CONTACT_DATA_ID) REFERENCES CONTACT_DATA(CONTACT_DATA_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(CONTACT_DATA_ID,TYPE_NAME)
);

create table CONTACT_ORG_REL_TYPES
(
    ORG_ID MEDIUMINT NOT NULL,
    TYPE_NAME VARCHAR(20) NOT NULL,
    FOREIGN KEY(ORG_ID) REFERENCES CONTACT_ORG(ORG_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(ORG_ID,TYPE_NAME)
);

create table CONTACT_DATA_REL_ENCODING_TYPES
(
    CONTACT_DATA_ID MEDIUMINT NOT NULL,
    ENCODING_TYPE_ID MEDIUMINT NOT NULL,
    FOREIGN KEY(CONTACT_DATA_ID) REFERENCES CONTACT_DATA(CONTACT_DATA_ID),
    FOREIGN KEY(ENCODING_TYPE_ID) REFERENCES CONTACT_ENCODING_TYPES(ENCODING_TYPE_ID)
        ON DELETE CASCADE ON UPDATE CASCADE,
    PRIMARY KEY(CONTACT_DATA_ID,ENCODING_TYPE_ID)
);
