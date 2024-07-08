package com.esap_be.demo.Models.Entities;

import com.esap_be.demo.Models.Dto.UserRoleDto;
import jakarta.persistence.*;
import lombok.*;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.security.core.authority.SimpleGrantedAuthority;
import org.springframework.security.core.userdetails.UserDetails;

import java.sql.Timestamp;
import java.util.Arrays;
import java.util.Collection;
import java.util.List;
import java.util.stream.Collectors;

@Entity
@Data
@Builder
@AllArgsConstructor
@NoArgsConstructor
@Getter
@Setter
@Table(name = "UserRoles")
public class UserRole implements UserDetails {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    @Column(name = "Id")
    private int id;

    @Column(name = "Username")
    @Basic(fetch = FetchType.EAGER)
    private String username;

    @Column(name = "Email")
    private String email;

    @Column(name = "DateOfCreation")
    private Timestamp dateOfCreation;

    @Column(name = "CreatedBy")
    private String createdBy;

    @Column(name = "Roles")
    private String roles;

    @Override
    public String toString() {
        return "UserRole{" +
                "id=" + id +
                ", username='" + username + '\'' +
                ", email='" + email + '\'' +
                ", dateOfCreation=" + dateOfCreation +
                ", createdBy='" + createdBy + '\'' +
                ", roles='" + roles + '\'' +
                '}';
    }

    public UserRoleDto toDto(UserRole userRole) {
        return new UserRoleDto(userRole.getId(), userRole.getUsername(), userRole.getEmail(), userRole.getDateOfCreation(), userRole.getCreatedBy(), userRole.getRoles());
    }

    @Override
    public Collection<? extends GrantedAuthority> getAuthorities() {
        return Arrays.stream(this.roles.split(";"))
                .map(SimpleGrantedAuthority::new)
                .collect(Collectors.toList());
    }

    public String getPassword() {
        return "";
    }

    @Override
    public String getUsername() {
        return email;
    }

    @Override
    public boolean isAccountNonExpired() {
        return true;
    }

    @Override
    public boolean isAccountNonLocked() {
        return true;
    }

    @Override
    public boolean isCredentialsNonExpired() {
        return true;
    }

    @Override
    public boolean isEnabled() {
        return true;
    }
}