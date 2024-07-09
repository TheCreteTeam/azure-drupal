package com.esap_be.demo.Repositories;

import com.esap_be.demo.Models.Entities.DataSet;
import org.springframework.data.repository.CrudRepository;
import org.springframework.stereotype.Repository;

@Repository
public interface IDataSetRepository extends CrudRepository<DataSet, String> {

}
