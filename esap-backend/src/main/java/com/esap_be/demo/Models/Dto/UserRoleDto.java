package com.esap_be.demo.Models.Dto;

import com.esap_be.demo.Models.Entities.UserRole;
import lombok.AllArgsConstructor;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.util.Date;

@AllArgsConstructor
@NoArgsConstructor
@Getter
@Setter
public class UserRoleDto {
    private int id;
    private String username;
    private String email;
    private Date dateOfCreation;
    private String createdBy;
    private String roles;


    @Override
    public String toString() {
        return "UserDto{" +
                "id=" + id +
                ", username='" + username + '\'' +
                ", email='" + email + '\'' +
                ", dateOfCreation=" + dateOfCreation +
                ", createdBy='" + createdBy + '\'' +
                ", roles='" + roles + '\'' +
                '}';
    }

    public UserRole toDto(UserRole userRole) {
        return new UserRole(userRole.getId(), userRole.getUsername(), userRole.getEmail(), userRole.getDateOfCreation(), userRole.getCreatedBy(), userRole.getRoles());
    }
}
