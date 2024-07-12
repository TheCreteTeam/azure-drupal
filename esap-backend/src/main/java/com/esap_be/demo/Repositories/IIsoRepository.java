package com.esap_be.demo.Repositories;

import com.esap_be.demo.Models.Entities.ISOCode;
import org.springframework.data.repository.CrudRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface IIsoRepository extends CrudRepository<ISOCode, String> {
}
