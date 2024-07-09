package com.esap_be.demo.Controllers;

import com.esap_be.demo.Models.Dto.UserRoleDto;
import com.esap_be.demo.Models.Entities.UserRole;
import com.esap_be.demo.Services.JwtService;
import com.esap_be.demo.Services.UserRoleService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
public class UserRoleController {

    @Autowired
    private UserRoleService userRoleService;

    @Autowired
    private JwtService jwtService;

    public UserRoleController(UserRoleService userRoleService) {
        this.userRoleService = userRoleService;
    }

    @GetMapping(value = "/users")
    @ResponseStatus(HttpStatus.OK)
    public ResponseEntity<List<UserRole>> getAllUsers() {
        List<UserRole> userRole = userRoleService.getAllUsers();

        userRole.forEach(System.out::println);

        return new ResponseEntity<>(userRole, HttpStatus.OK);
    }

    @GetMapping(value = "/someUser")
    @ResponseStatus(HttpStatus.OK)
    public ResponseEntity<UserRoleDto> getSomeUser() {
        UserRoleDto userRole = userRoleService.getUserDtoById(1);

        System.out.println(userRole.toString());

        return new ResponseEntity<>(userRole, HttpStatus.OK);
    }

    @PostMapping(value = "/addUser")
    @ResponseStatus(HttpStatus.CREATED)
//    @PreAuthorize("hasRole('ROLE_ADMIN')")
    public ResponseEntity<String> addUser(@RequestBody UserRole userRole) {
        userRoleService.addUser(userRole);

        return new ResponseEntity<>("User added successfully", HttpStatus.CREATED);
    }

    @GetMapping(value = "/auth/login")
    @ResponseStatus(HttpStatus.OK)
    public ResponseEntity<String> test() {

        var user = userRoleService.getUserById(1);

        String jwtToken = jwtService.generateToken(user);

        return new ResponseEntity<>(jwtToken, HttpStatus.OK);
    }
}
