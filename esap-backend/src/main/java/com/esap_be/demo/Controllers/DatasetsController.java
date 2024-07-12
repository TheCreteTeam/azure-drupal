package com.esap_be.demo.Controllers;

import com.esap_be.demo.Models.Entities.DataSet;
import com.esap_be.demo.Repositories.IDataSetRepository;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.Date;

@RestController
public class DatasetsController {

    private final IDataSetRepository dataSetRepository;

    public DatasetsController(IDataSetRepository dataSetRepository) {
        this.dataSetRepository = dataSetRepository;
    }

    @GetMapping(value = "/HelloWorld")
    @PreAuthorize("hasRole('CA_Admin')")
    public ResponseEntity<String> HelloWorld() {
        return new ResponseEntity<>("Hello World", HttpStatus.OK);
    }

    @PostMapping(value = "/SaveDataSet")
    public ResponseEntity<DataSet> SaveDataSet(@RequestBody DataSet dataSet) {
        DataSet savedDataSet = dataSetRepository.save(dataSet);
        return new ResponseEntity<>(savedDataSet, HttpStatus.CREATED);
    }

    @GetMapping(value = "/GetDataSet/{id}")
    public ResponseEntity<DataSet> GetDataSet(@PathVariable int id) {
        DataSet dataSet = dataSetRepository.findById(String.valueOf(id)).orElse(null);
        if (dataSet != null) {
            return new ResponseEntity<>(dataSet, HttpStatus.OK);
        } else {
            return new ResponseEntity<>(HttpStatus.NOT_FOUND);
        }
    }

    @DeleteMapping(value = "/DeleteDataSet/{id}")
    public ResponseEntity<HttpStatus> DeleteDataSet(@PathVariable String id) {
        dataSetRepository.deleteById(id);
        return new ResponseEntity<>(HttpStatus.OK);
    }

    @PutMapping(value = "/UpdateDataSet/{id}")
    public ResponseEntity<DataSet> UpdateDataSet(@PathVariable String id, @RequestBody DataSet updatedDataSet) {
        return dataSetRepository.findById(id)
                .map(dataSet -> {
                    dataSet.setName(updatedDataSet.getName());
                    dataSet.setDescription(updatedDataSet.getDescription());
                    dataSet.setCreationDate(new Date());
                    DataSet savedDataSet = dataSetRepository.save(dataSet);
                    return new ResponseEntity<>(savedDataSet, HttpStatus.OK);
                })
                .orElseGet(() -> new ResponseEntity<>(HttpStatus.NOT_FOUND));
    }

    @GetMapping(value = "/GetAllDataSets")
    public ResponseEntity<Iterable<DataSet>> GetAllDataSets() {
        Iterable<DataSet> dataSets = dataSetRepository.findAll();
        return new ResponseEntity<>(dataSets, HttpStatus.OK);
    }
}
