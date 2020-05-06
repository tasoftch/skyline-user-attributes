/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

BEGIN TRANSACTION ;

create table SKY_USER_ATTRIBUTE
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    enabled varchar(1) default '0' null,
    name varchar(20) not null,
    description text default null,
    intern varchar(1) default '0' not null,
    icon varchar(20) default null,
    valueType varchar(100) not null,
    multiple int default 0 not null,
    attr_group int default null,
    indexing int default 10 not null
);

insert into SKY_USER_ATTRIBUTE (id, enabled, name, description, intern, icon, valueType) VALUES
    (1, 1, 'Logo', 'An image media object with the users logo', 1, 'image', 'string'),
    (2, 0, 'Departement', 'Position or description of departement', 0, 'building', 'string'),
    (3, 0, 'Status', 'Status message', 0, 'comment', 'text'),
    (4, 0, 'Options', 'Publishing options. Some defined by Skyline CMS and some specified by your application', 1, NULL, 'int'),
    (5, 1, 'Email', 'Public Emailadress', 0, 'envelope', 'string<email>'),
    (6, 1, 'WWW', 'Public Homepage Address', 0, 'globe', 'string<url>'),
    (7, 1, 'WhatsApp', NULL, 0, 'fab.whatsapp', 'string'),
    (8, 1, 'Facebook', NULL, 0, 'fab.facebook', 'string'),
    (9, 1, 'Twitter', NULL, 0, 'fab.twitter', 'string'),
    (10, 1, 'YouTube', NULL, 0, 'fab.youtube', 'string'),
    (11, 1, 'Instagram', NULL, 0, 'fab.instagram', 'string'),
    (12, 1, 'SnapChat', NULL, 0, 'fab.snapchat', 'string'),
    (13, 1, 'LinkedIn', NULL, 0, 'fab.linkedin', 'string'),
    (14, 1, 'Address', 'Your public address', 1, 'home', 'Skyline\\Admin\\Tool\\Attribute\\Value\\Address'),
    (15, 1, 'Birthday', 'The date of birth', 0, 'cake', 'date'),
    (16, 1, 'Telefone', NULL, 0, 'fas.phone', 'string'),
    (17, 1, 'Mobile', NULL, 0, 'fas.mobile-alt', 'string')
;

create table SKY_USER_ATTRIBUTE_GROUP
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name varchar(50) not null,
    description text default null,
    options int default 0 not null
);

create table SKY_USER_ATTRIBUTE_Q
(
    user int not null,
    attribute int not null,
    options int null,
    value blob null
);

COMMIT ;