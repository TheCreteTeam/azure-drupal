package com.esap_be.demo.Services;

import com.esap_be.demo.Models.Dto.UserRoleDto;
import com.esap_be.demo.Models.Entities.UserRole;
import com.esap_be.demo.Repositories.UserRoleRepository;
import com.esap_be.demo.Services.interfaces.IUserRoleService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.security.core.authority.SimpleGrantedAuthority;
import org.springframework.stereotype.Service;

import javax.management.relation.Role;
import java.util.*;

@Service
public class UserRoleService implements IUserRoleService {

    @Autowired
    private UserRoleRepository userRoleRepository;

    public UserRoleDto getUserDtoById(int id) {
       UserRole userRole = userRoleRepository.findById(id).orElse(null);

        assert userRole != null;

        return userRole.toDto(userRole);
    }

    public UserRole getUserById(int id) {
       UserRole userRole = userRoleRepository.findById(id).orElse(null);

        assert userRole != null;

        return userRole;
    }

    public List<UserRole> getAllUsers() {
        return userRoleRepository.findAll();
    }

    public void addUser(UserRole userRole) {
        userRoleRepository.save(userRole);
    }
}
