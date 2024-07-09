package com.esap_be.demo;

import com.esap_be.demo.Services.UserRoleService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;

@SpringBootApplication
public class EsapBackendApplication {

	@Autowired
	private UserRoleService userService;

	public static void main(String[] args) {
		SpringApplication.run(EsapBackendApplication.class, args);
	}

//	@Override
//	public void run(String... args) throws Exception {
//		System.out.println("Hello World");
////		List<UserRole> userRole = userService.getAllUsers();
////
////		userRole.forEach(System.out::println);
//
//		UserDto userDto = userService.getUserById(1);
//		System.out.println(userDto.toString());
//	}
}
