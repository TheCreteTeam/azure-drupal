package com.esap_be.demo.Controllers;

import com.esap_be.demo.Models.Entities.ISOCodeDetails;
import com.esap_be.demo.Models.Entities.ISOCode;
import com.esap_be.demo.Repositories.IIsoRepository;
import com.esap_be.demo.Services.CustomISOCodeDetailsDeserializer;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.module.SimpleModule;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.client.RestTemplate;

import java.util.List;
import java.util.Map;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

@RestController
public class ISOController {

    private static final Logger logger = LoggerFactory.getLogger(ISOController.class);

    private final IIsoRepository isoRepository;

    private final String getAllISOCodesURL = "https://iso3166-updates.com/api/all";

    public ISOController(IIsoRepository isoRepository) {
        this.isoRepository = isoRepository;
    }

    @PostMapping(value = "/SaveAllISOs")
    public ResponseEntity<String> saveAllISOs() {
        RestTemplate restTemplate = new RestTemplate();
        ResponseEntity<String> response = restTemplate.getForEntity(getAllISOCodesURL, String.class);

        if (response.getStatusCode() == HttpStatus.OK && response.getBody() != null) {
            logger.info("ISO codes fetched successfully.");
            ObjectMapper objectMapper = new ObjectMapper();
            
            SimpleModule module = new SimpleModule();
            module.addDeserializer(Map.class, new CustomISOCodeDetailsDeserializer());
            objectMapper.registerModule(module);

            try {
                Map<String, List<ISOCodeDetails>> isoDataMap =
                        objectMapper.readValue(response.getBody(), new TypeReference<Map<String, List<ISOCodeDetails>>>() {});

                for (Map.Entry<String, List<ISOCodeDetails>> entry : isoDataMap.entrySet()) {
                    ISOCode isoCode = new ISOCode(entry.getKey(), entry.getValue());
                    isoRepository.save(isoCode);
                }

                return new ResponseEntity<>("ISO codes saved successfully.", HttpStatus.CREATED);
            } catch (JsonProcessingException e) {
                logger.error("Failed to deserialize ISO codes.", e);
                return new ResponseEntity<>("Failed to deserialize ISO codes.", HttpStatus.INTERNAL_SERVER_ERROR);
            }
        } else {
            logger.error("Failed to fetch ISO codes.");
            return new ResponseEntity<>("Failed to fetch ISO codes.", HttpStatus.INTERNAL_SERVER_ERROR);
        }
    }

    @GetMapping(value = "/GetISO/{code}")
    public ResponseEntity<ISOCode> GetISO(@PathVariable String code) {
        ISOCode isoCode = isoRepository.findById(code).orElse(null);
        if (isoCode != null) {
            return new ResponseEntity<>(isoCode, HttpStatus.OK);
        } else {
            return new ResponseEntity<>(HttpStatus.NOT_FOUND);
        }
    }

    @GetMapping(value = "/GetAllISOs")
    public ResponseEntity<Iterable<ISOCode>> GetAllISOs() {
        Iterable<ISOCode> isoCodes = isoRepository.findAll();
        return new ResponseEntity<>(isoCodes, HttpStatus.OK);
    }

    // Clear redis cache
    @GetMapping(value = "/ClearISOsCache")
    public ResponseEntity<String> ClearISOs() {
        isoRepository.deleteAll();
        return new ResponseEntity<>("ISO codes cleared successfully.", HttpStatus.OK);
    }
}
