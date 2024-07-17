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
}
