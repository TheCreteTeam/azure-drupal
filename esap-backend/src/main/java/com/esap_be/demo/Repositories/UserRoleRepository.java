package com.esap_be.demo.Repositories;

import com.esap_be.demo.Models.Entities.UserRole;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.Optional;
@Repository
public interface UserRoleRepository extends JpaRepository<UserRole, Integer>{
    Optional<UserRole> findByEmail(String email);
}

