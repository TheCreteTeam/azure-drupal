CREATE TABLE UserRoles(
                          Id INT NOT NULL PRIMARY KEY IDENTITY (1, 1),
                          Username VARCHAR(50) NOT NULL,
                          Email VARCHAR(50) NOT NULL,
                          DateOfCreation DATETIME2 NOT NULL,
                          CreatedBy VARCHAR(50) NOT NULL,
                          Roles VARCHAR(100) NOT NULL,
);

INSERT INTO UserRoles (Username, Email, DateOfCreation, CreatedBy, Roles)
VALUES ('testUser', 'test@mail.com', '2021-01-01 00:00:00', 'admin', 'CA_ADMIN;CA_MANAGER;CA_VIEWER');